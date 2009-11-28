// Project Tools Dropdown
function PTIssue(id_issue, saveURL, id_last_event, element_events)
{
	var changes = [];
	var callbacks = [];
	
	var formItems = [];
	
	var saveInProgress = false;
	
	this.id_issue = id_issue;
	this.id_last_event = id_last_event;
	
	this.element_events = document.getElementById(element_events);
	
	this.addLabel = addLabel;
	this.addDropdown = addDropdown;
	this.addMultiDropdown = addMultiDropdown;
	
	this.addChange = addChange;
	this.addCallback = addCallback;
	this.saveChanges = saveChanges;
	
	function addLabel(item, fieldName)
	{
		formItems[fieldName] = new PTLabel(this, item, fieldName);
		
		return formItems[fieldName];
	}

	function addDropdown(item, fieldName, value)
	{
		formItems[fieldName] = new PTDropdown(this, item, fieldName, value);
		
		return formItems[fieldName];
	}

	function addMultiDropdown(item, fieldName)
	{
		formItems[fieldName] = new PTMultiDropdown(this, item, fieldName);
		
		return formItems[fieldName];
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
		
		i = changes.length;
		changes[i] = "last_event=" + this.id_last_event;
		
		sendXMLDocument(saveURL, changes.join("&"), onSaveDone);
		
		changes = [];
	}
	
	function onSaveDone(oXMLDoc)
	{
		for (i = 0; i < callbacks.length; i++)
			callbacks[i](oXMLDoc);
		
		// Call setValue for each item
		var nodes = oXMLDoc.getElementsByTagName('update');
		
		for (var i = 0; i < nodes.length; i++)
		{
			node = nodes[i];
			fieldName = node.getAttribute("field");
			
			if (formItems[fieldName] != undefined)
				formItems[fieldName].setValue(node.getAttribute("id"), node.textContent);
		}
		
		var events_html = oXMLDoc.getElementsByTagName('events_html');
		
		if (events_html)
			element_events.innerHTML = events_html[0].textContent;
			
		// Reset callbacks
		callbacks = [];
		
		saveInProgress = false;
	}
}

function PTLabel(issue, name, fieldName)
{
	var labelHandle = document.getElementById(name);
	var labelDL = labelHandle.getElementsByTagName('dl')[0];
	var labelItem = labelDL.getElementsByTagName('dt')[0];
	var labelValue = labelDL.getElementsByTagName('dd')[0];

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
	var optionsID = [];
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
	this.fieldName = fieldName;

	this.addOption = addOption;
	this.setValue = setValue;

	function addOption(id, text, style)
	{
		i = optionsID.length;
		optionsID[i] = id;
		
		options[id] = new Array(3);
		options[id]['id'] = id;
		options[id]['name'] = text;

		if (style == undefined)
			style = "";

		options[id]['style'] = style;
	}

	function setValue(id, value)
	{
		selectedValue = id;
		selectedItem = options[id];
		
		dropdownValue.innerHTML = selectedItem['name'];
	}
	
	function dropDownHide()
	{
		dropdownHandle.removeChild(dropdownMenu);
		visible = false;
		dropdownHandle.className = dropdownHandle.classNameOld;
	}

	function dropDownShow()
	{
		if (optionsID.length == 0)
			return;
		
		dropdownHandle.classNameOld = dropdownHandle.className;
		dropdownHandle.className += " project_dropdown_selected";

		dropdownMenu = document.createElement('ul');

		dropdownMenu.style.width = (dropdownHandle.clientWidth) + "px";

		for (i = 0; i < optionsID.length; i++)
		{
			optionID = optionsID[i];
			
			newOption = document.createElement('li');
			newOption.optionValue = options[optionID]['id'];
			newOption.optionItem = options[optionID];
			newOption.className = options[optionID]['style'];
			newOption.innerHTML = options[optionID]['name'];

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
			dropDownHide();
		else
			dropDownShow();
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
		dropdownHandle.className += " project_dropdown";
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

function PTMultiDropdown(issue, name, fieldName)
{
	var optionsID = [];
	var options = [];
	var visible = false;
	var dropdownHandle = document.getElementById(name);
	var dropdownMenu = null;
	var dropdownDL = dropdownHandle.getElementsByTagName('dl')[0];
	var dropdownItem = dropdownDL.getElementsByTagName('dt')[0];
	var dropdownValue = dropdownDL.getElementsByTagName('dd')[0];
	var dropdownBtn = null;
	var handled = true;
	var changed = false;

	this.issue = issue;
	this.fieldName = fieldName;

	this.addOption = addOption;
	this.setValue = setValue;

	function addOption(id, text, selected, style)
	{
		i = optionsID.length;
		optionsID[i] = id;
		
		options[id] = new Array(3);
		options[id]['id'] = id;
		options[id]['name'] = text;
		options[id]['selected'] = selected == 1 ? true : false;

		if (style == undefined)
			style = "";
	
		options[id]['style'] = style;
	}

	function setValue(id, value)
	{
		dropdownValue.innerHTML = value;
	}
	
	function dropDownHide()
	{
		dropdownHandle.removeChild(dropdownMenu);
		visible = false;
		dropdownHandle.className = dropdownHandle.classNameOld;
		
		if (changed)
		{
			dropdownBtn.className = "button_work";
			
			value = ""
			
			for (i = 0; i < optionsID.length; i++)
			{
				optionID = optionsID[i];
				
				if (value != "" && options[optionID]['selected'])
					value += "," + options[optionID]['id'];
				else if (options[optionID]['selected'])
					value = options[optionID]['id'];
			}
			
			// If none set value to 0
			if (value == "")
				value = "0";

			// Register change and add callback to update status after request is done	
			issue.addChange(fieldName, value);
			issue.addCallback(saveDone)

			// Save changes now
			issue.saveChanges();
			
			changed = false;
		}
	}

	function dropDownShow()
	{
		if (optionsID.length == 0)
			return;
		
		dropdownHandle.classNameOld = dropdownHandle.className;
		dropdownHandle.className += " project_dropdown_selected";

		dropdownMenu = document.createElement('ul');

		dropdownMenu.style.width = (dropdownHandle.clientWidth) + "px";

		for (i = 0; i < optionsID.length; i++)
		{
			optionID = optionsID[i];
			
			newOption = document.createElement('li');
			newOption.optionID = optionID;
			newOption.className = options[optionID]['style'] + (options[optionID]['selected'] ? " selected_item" : "");
			newOption.innerHTML = options[optionID]['name'];

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
			dropDownHide();
		else
			dropDownShow();
	}

	function dropDownItemClick(evt)
	{
		handled = true;
		changed = true;

		var target = (evt.target) ? evt.target : evt.srcElement;
		
		options[target.optionID]['selected'] = !options[target.optionID]['selected'];
		
		target.className = options[target.optionID]['style'] + (options[target.optionID]['selected'] ? " selected_item" : "");
	}
	
	function saveDone(oXMLDoc)
	{
		dropdownBtn.className = "button";
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
		dropdownHandle.className += " project_dropdown";
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