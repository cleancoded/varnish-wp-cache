// our id for our rows, we can have rows added and removed
// so we need a base
var rowCount = 0;

function createRow(tableID, id, addr, port, secret) {
	var row = document.createElement ('tr');
	var td1 = document.createElement ('td');
	var td2 = document.createElement ('td');
	var td3 = document.createElement ('td');
	var td4 = document.createElement ('td');
	var cleancoded_addr = document.createElement ('input');
	var cleancoded_port = document.createElement ('input');
	var cleancoded_secret = document.createElement ('input');
	var dRow = document.createElement ('input');

	cleancoded_addr.className = "regular-text";
	cleancoded_addr.type = "text";
	cleancoded_addr.id = id;
	cleancoded_addr.name = "cleancodedVarnish_addr[]";
	cleancoded_addr.value = addr || "";

	cleancoded_port.className = "small-text";
	cleancoded_port.type = "text";
	cleancoded_port.id = id;
	cleancoded_port.name = "cleancodedVarnish_port[]";
	cleancoded_port.value = port || "";

	cleancoded_secret.className = "regular-text";
	cleancoded_secret.type = "text";
	cleancoded_secret.id = id;
	cleancoded_secret.name = "cleancodedVarnish_secret[]";
	cleancoded_secret.value = secret || "";

	dRow.className = "";
	dRow.type = "button";
	dRow.name = "deleteRow";
	dRow.value = "-";
	dRow.id = id;
	dRow.onclick = function () { deleteRow(tableID, id); }

	td1.appendChild (cleancoded_addr);
	td2.appendChild (cleancoded_port);
	td3.appendChild (cleancoded_secret);
	td4.appendChild (dRow);
	row.appendChild (td1);
	row.appendChild (td2);
	row.appendChild (td3);
	row.appendChild (td4);

	return row;
}

function addRow(tableID, id, addr, port, secret) {
	var tbody = document.getElementById(tableID).getElementsByTagName ('tbody')[0];

	rowCount++;
	var row = createRow(tableID, id, addr, port, secret);

	tbody.appendChild (row);
}

function deleteRow(tableID, rowID) {
	try {
		var tbody = document.getElementById(tableID).getElementsByTagName ('tbody')[0];
		var trs = tbody.getElementsByTagName ('tr');

		// the id = 0 we don't want to remove, as it is the header
		for (var i = 1; i < trs.length; i++) {
			// we use our own id, let's not mix up with table ids
			var id = (trs[i].getElementsByTagName ('input')[0]).id;
			if (id == rowID) {
				tbody.deleteRow (i);
				return;
			}
		}
	} catch(e) {
		alert(e);
	}
}
