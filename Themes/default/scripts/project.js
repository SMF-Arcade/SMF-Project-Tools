// Project Tools Dropdown
function PTIssue(id_issue, saveURL)
{
	var changes = [];
	var callbacks = [];
	
	var items = [];
	
	var saveInProgress = false;
	
	this.id_issue = id_issue;
	
	this.addChange = addChange;
	this.addLabel = addLabel;
	this.addCallback = addCallback;
	this.saveChanges = saveChanges;
	
	function addLabel(item)
	{
		items[item] = new Array(2);
		items[item]['id'] = item;
		items[item]['object'] = PTLabel(this, item);
	}
	
	function addCallback(callback)
	{
		i = callbacks.length;
		callbacks[i] = callback;
	}
	
	function addChange(item, value)
	{
		if (saveInProgress)
			return setTimeout(addChange, 500, item, value);
			
		i = changes.length;
		changes[i] = item + "=" + value;
		
		return true;
	}
	
	function saveChanges()
	{
		if (saveInProgress)
			return;
		
		saveInProgress = true;
		
		sendXMLDocument(saveURL, changes.join("&"), onSaveDone);
		changes = [];
	}
	
	function onSaveDone(oXMLDoc)
	{
		for (i = 0; i < callbacks.length; i++)
		{
			callbacks[i](oXMLDoc);
		}
		
		// Call setValue for each item
		var nodes = oXMLDoc.getElementsByTagName('update');
		
		for (var i = 0; i < nodes.length; i++)
		{
			field = nodes[i].getAttribute("field");
			
			if (items[field] != undefined && node.nodeValue != '' && node.nodeValue != null && node.nodeValue != undefined)
			{
				dropdownValue.innerHTML = nodes[i].nodeValue;
				
				items[field]['object'].setValue(nodes[i].getAttribute("id"), node.nodeValue);
				
				return;
			}
		}
				
		
		// Reset callbacks
		callbacks = [];
		
		saveInProgress = false;
	}
}

function PTLabel(issue, name)
{
	var labelHandle = document.getElementById(name);
	var labelDL = dropdownHandle.getElementsByTagName('dl')[0];
	var labelItem = dropdownDL.getElementsByTagName('dt')[0];
	var labelValue = dropdownDL.getElementsByTagName('dd')[0];

	this.issue = issue;
	this.fieldName = fieldName;
	this.setValue = setValue;

	function setValue(id, value)
	{
		labelValue.innerHTML = value; 
	}
}

function PTDropdown(issue, name, fieldName, selectedValue)
{
	var options = [];
	var visible = false;
	var dropdownHandle = document.getElementById(name);
	var dropdownMenu = null;
	var dropdownDL = dropdownHandle.getElementsByTagName('dl')[0];
	var dropdownItem = dropdownDL.getElementsByTagName('dt')[0];
	var dropdownValue = dropdownDL.getElementsByTagName('dd')[0];
	var dropdownBtn = null;
	var handled = true;
	var selectedItem = null;

	this.issue = issue;
	this.addOption = addOption;
	this.fieldName = fieldName;

	function addOption(id, text, style)
	{
		i = options.length;
		options[i] = new Array(3);
		options[i]['id'] = id;
		options[i]['name'] = text;

		if (style == undefined)
			style = "";

		options[i]['style'] = style;
	}

	function dropDownHide()
	{
		dropdownHandle.removeChild(dropdownMenu);
		visible = false;
		dropdownHandle.className = dropdownHandle.classNameOld;
	}

	function dropDownShow()
	{
		dropdownHandle.classNameOld = dropdownHandle.className;
		dropdownHandle.className += " dropdown_selected";

		dropdownMenu = document.createElement('ul');

		dropdownMenu.style.width = (dropdownHandle.clientWidth) + "px";

		for (i = 0; i < options.length; i++)
		{
			newOption = document.createElement('li');
			newOption.optionValue = options[i]['id'];
			newOption.optionItem = options[i];
			newOption.className = options[i]['style'];
			newOption.innerHTML = options[i]['name'];

			createEventListener(newOption);
			newOption.addEventListener('click', dropDownItemClick, false);

			dropdownMenu.appendChild(newOption);
		}

		dropdownHandle.appendChild(dropdownMenu);

		visible = true;
	}

	function dropDownChange(evt)
	{
		handled = true;

		var target = (evt.target) ? evt.target : evt.srcElement;

		if (target.tagName == 'A' && checkParent(target))
			return;

		if (visible)
		{
			dropDownHide();
		}
		else
		{
			dropDownShow();
		}
	}

	function dropDownItemClick(evt)
	{
		handled = true;

		var target = (evt.target) ? evt.target : evt.srcElement;

		if (target.optionValue != selectedValue)
		{
			selectedValue = target.optionValue;
			selectedItem = target.optionItem

			dropdownBtn.className = "button_work";

			// Register change and add callback to update status after request is done	
			issue.addChange(fieldName, selectedValue);
			issue.addCallback(saveDone)

			// Save changes now
			issue.saveChanges();
		}

		dropDownHide();
	}
	
	function saveDone(oXMLDoc)
	{
		dropdownBtn.className = "button";
		
		var nodes = oXMLDoc.getElementsByTagName('update');
		
		for (var i = 0; i < nodes.length; i++)
		{
			field = nodes[i].getAttribute("field"); 
			
			if (field == fieldName && node.nodeValue != '' && node.nodeValue != null && node.nodeValue != undefined)
			{
				dropdownValue.innerHTML = nodes[i].nodeValue;
				
				return;
			}
		}
		
		dropdownValue.innerHTML = selectedItem['name'];
	}

	function checkParent(domItem)
	{
		if (domItem == dropdownHandle)
			return true;
		else if (domItem.tagName == 'BODY')
			return false;
		else if (domItem.parentNode.tagName != 'BODY')
			return checkParent(domItem.parentNode);
		else
			return false;
	}

	function bodyClick(evt)
	{
		if (handled)
		{
			handled = false;
			return;
		}

		handled = false;

		if (visible)
		{
			var target = (evt.target) ? evt.target : evt.srcElement;

			if (!checkParent(target))
			{
				dropDownChange(evt);
			}
		}
	}

	function init()
	{
		dropdownHandle.className += " dropdown";
		createEventListener(dropdownDL);
		createEventListener(document);
		dropdownDL.addEventListener('click', dropDownChange, false);
		document.addEventListener('click', bodyClick, false);
		dropdownBtn = document.createElement('dd');
		dropdownBtn.className = "button";

		dropdownDL.appendChild(dropdownBtn);

		return true;
	}

	init();
}