
function PTDropdown(name, fieldName, currentValue, callback)
{
	var object;
	var options = [];
	var visible = false;
	var dropdownHandle = document.getElementById(name);
	var dropdownMenu = null;
	var dropdownDL = dropdownHandle.getElementsByTagName('dl')[0];
	var dropdownItem = dropdownDL.getElementsByTagName('dt')[0];
	var dropdownValue = dropdownDL.getElementsByTagName('dd')[0];
	var dropdownBtn = null;
	var handled = true;

	this.addOption = addOption;
	this.fieldName = fieldName;

	function addOption(id, text)
	{
		i = options.length;
		options[i] = new Array(2);
		options[i]['id'] = id;
		options[i]['name'] = text;
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
			createEventListener(dropDownItemClick);
			newOption.addEventListener('click', dropDownItemClick, false);
			newOption.innerHTML = options[i]['name'];

			dropdownMenu.appendChild(newOption);
		}

		dropdownHandle.appendChild(dropdownMenu);

		visible = true;
	}

	function dropDownChange(evt)
	{
		handled = true;

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

		if (evt.target.optionValue != currentValue)
		{
			dropdownBtn.className = "button_work";
			callback(fieldName, name, evt.target.optionValue);
			dropdownBtn.className = "button_button";
		}

		dropDownHide();
	}

	function checkParent(domItem)
	{
		if (domItem == dropdownHandle)
			return true;
		else if (domItem.tagName == 'BODY')
		{
			return false;
		}
		else
		{
			if (domItem.parentNode.tagName != 'BODY')
				return checkParent(domItem.parentNode);

			return false;
		}
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
			if (!checkParent(evt.target))
			{
				dropDownChange(evt);
			}
		}
	}

	function init()
	{
		dropdownHandle.className += " dropdown";
		createEventListener(dropdownDL);
		dropdownDL.addEventListener('click', dropDownChange, false);
		document.addEventListener('click', bodyClick, false);
		dropdownBtn = document.createElement('dd');
		dropdownBtn.className = "button";

		dropdownDL.appendChild(dropdownBtn);

		return true;
	}

	init();
}

function PTDCallback(fieldName, name, value)
{
	xmlRequestHandle = null;

	xmlRequestHandle = getXMLDocument(smf_prepareScriptUrl(smf_scripturl) + 'issue=' + currentIssue + ';sa=update;name=' + name + ';' + fieldName + '=' + value + ';xml', function (oXMLDoc)
		{
			if (xmlRequestHandle.readyState != 4)
				return true;

			alert(fieldName + ": " + value);

			return true;
		}
	);

	while (xmlRequestHandle.readyState != 4)
	{
	}
	return true;
}