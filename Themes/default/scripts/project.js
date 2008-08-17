// Project Tools Dropdown
function PTDropdown(name, fieldName, selectedValue, currentIssue, callback, sessionID)
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
	var selectedItem = null;

	this.currentIssue = currentIssue;
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
			createEventListener(dropDownItemClick);
			newOption.addEventListener('click', dropDownItemClick, false);
			newOption.innerHTML = '<span style="' + options[i]['style'] + '">' + options[i]['name'] + '</span>';

			dropdownMenu.appendChild(newOption);
		}

		dropdownHandle.appendChild(dropdownMenu);

		visible = true;
	}

	function dropDownChange(evt)
	{
		handled = true;

		if (evt.target.tagName == 'A' && checkParent(evt.target))
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

		target = evt.target;

		if (target.tagName == 'SPAN')
			target = target.parentNode;

		if (target.optionValue != selectedValue)
		{
			selectedValue = target.optionValue;
			selectedItem = target.optionItem

			dropdownBtn.className = "button_work";

			//xmlRequestHandle = callback(fieldName, name, target.optionValue, currentIssue, sessionID);

			xmlRequestHandle = getXMLDocument(smf_prepareScriptUrl(smf_scripturl) + 'issue=' + currentIssue + ';sa=update;name=' + name + ';' + fieldName + '=' + selectedValue + ';xml;sesc=' + sessionID,
				function (oXMLDoc)
				{
					if (xmlRequestHandle.readyState != 4)
						return true;

					return true;
				}
			);


			checkReadyState(xmlRequestHandle);
		}

		dropDownHide();
	}

	function checkReadyState(xmlRequestHandle)
	{
		if (xmlRequestHandle.readyState == 4)
		{
			dropdownBtn.className = "button";

			var node = xmlRequestHandle.responseXML.getElementsByTagName('update')[0];

			if (node.nodeValue == '' || node.nodeValue == null || node.nodeValue == undefined)
			{
				dropdownValue.innerHTML = selectedItem['name'];
			}
			else
			{
				dropdownValue.innerHTML = xmlRequestHandle.responseXML.getElementsByTagName('update')[0].nodeValue;
			}
		}
		else
		{
			setTimeout(checkReadyState, 500, xmlRequestHandle);
		}
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