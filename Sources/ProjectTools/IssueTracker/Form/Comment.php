<?php
/**
 * 
 *
 * @package ProjectTools
 * @subpackage IssueTracker
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 *
 */
class ProjectTools_IssueTracker_Form_Comment extends ProjectTools_Form_Project
{
	/**
	 *
	 *
	 */
	public $formid = 'issuecomment';

	/**
	 * @var ProjectTools_IssueTracker_Issue
	 */
	protected $issue;

	/**
	 * @var int Comment ID
	 */
	protected $comment;
	
	/**
	 *
	 */
	final public function __construct($id_project, $id_issue, $id_comment = null, $is_fatal = true, $is_post = null)
	{
		global $txt, $sourcedir, $smcFunc;
		
		parent::__construct((int) $id_project, $is_fatal, $is_post);
		
		$this->issue = ProjectTools_IssueTracker_Issue::getIssue($id_issue);
		
		$this->comment = $id_comment;
		
		$this->data = array();
		
		//
		if ($id_comment !== null)
		{
			
			new Madjoki_Form_Element_Header($this, sprintf($txt['edit_comment'], $this->issue->id, $this->issue->name));
		}
		else
		{
			new Madjoki_Form_Element_Header($this, $txt['comment_issue']);
			
			if (isset($_REQUEST['quote']))
			{
				checkSession('get');
		
				require_once($sourcedir . '/Subs-Post.php');
		
				$request = $smcFunc['db_query']('', '
					SELECT c.id_comment, iv.event_time, c.edit_time, c.body,
						IFNULL(mem.real_name, iv.poster_name) AS real_name, iv.poster_email, iv.poster_ip, iv.id_member
					FROM {db_prefix}issue_comments AS c
						INNER JOIN {db_prefix}issue_events AS iv ON (iv.id_comment = c.id_comment)
						LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = iv.id_member)
					WHERE c.id_comment = {int:comment}',
					array(
						'issue' => $this->issue->id,
						'comment' => $_REQUEST['quote'],
					)
				);
		
				$row = $smcFunc['db_fetch_assoc']($request);
				$smcFunc['db_free_result']($request);
		
				if (!$row)
					fatal_lang_error('comment_not_found', false);
		
				$this->data['comment'] = $row['body'];
				censorText($this->data['comment']);
		
				// fix html tags
				if (strpos($this->data['comment'], '[html]') !== false)
				{
					$parts = preg_split('~(\[/code\]|\[code(?:=[^\]]+)?\])~i', $this->data['comment'], -1, PREG_SPLIT_DELIM_CAPTURE);
					for ($i = 0, $n = count($parts); $i < $n; $i++)
					{
						// It goes 0 = outside, 1 = begin tag, 2 = inside, 3 = close tag, repeat.
						if ($i % 4 == 0)
							$parts[$i] = preg_replace('~\[html\](.+?)\[/html\]~ise', '\'[html]\' . preg_replace(\'~<br\s?/?>~i\', \'&lt;br /&gt;<br />\', \'$1\') . \'[/html]\'', $parts[$i]);
					}
					$this->data['comment'] = implode('', $parts);
				}
		
				$this->data['comment'] = preg_replace('~<br(?: /)?' . '>~i', "\n", $this->data['comment']);
				$this->data['comment'] = '[quote author=' . $row['real_name'] . ' link=' . 'issue=' . $this->issue->id . '.com' . $_REQUEST['quote'] . '#com' . $_REQUEST['quote'] . ' date=' . $row['event_time'] . "]\n" . rtrim($this->data['comment']) . "\n[/quote]";
			}
		}
		
		// BBC Editor
		new Madjoki_Form_Element_BBCEditor($this, 'comment', '', new Madjoki_Form_Validator_BBC(array('no_empty' => true)));
		
		$this->saveEntities = array('comment');
		
		//
		new Madjoki_Form_Element_Divider($this);
		
		if ($id_issue !== null)
			new Madjoki_Form_Element_Submit($this, $txt['save']);
		else
			new Madjoki_Form_Element_Submit($this, $txt['report']);
	}
	
	/**
	 *
	 */
	final public function Save()
	{
		global $smcFunc, $user_info;
		
		if (!$this->is_post)
			return false;
		
		if (!$this->Validate())
			return false;
		
		$commentOptions = array();
		
		foreach ($this->elements as $element)
		{
			if ($element instanceof Madjoki_Form_Element_Field)
				$commentOptions[$element->getDataField()] = $element->getValue();
		}
		
		$_POST['guestname'] = $user_info['username'];
		$_POST['email'] = $user_info['email'];
		
		$posterOptions = array(
			'id' => $user_info['id'],
			'ip' => $user_info['ip'],
			'name' => $user_info['is_guest'] ? $_POST['guestname'] : $user_info['name'],
			'username' => $_POST['guestname'],
			'email' => $_POST['email'],
		);
		
		if ($this->comment !== null)
		{
			
		}
		// Create New
		else
			list ($this->comment, $id_event) = ProjectTools_IssueTracker::createComment($this->project->id, $this->issue->id, $commentOptions, $posterOptions);
			
		return array($this->comment, isset($id_event) ? $id_event : 0);
	}
}

?>