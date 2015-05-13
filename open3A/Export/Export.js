/*
 *
 *  This file is part of open3A.

 *  open3A is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.

 *  open3A is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 *  2007 - 2015, Rainer Furtmeier - Rainer@Furtmeier.IT
 */
 
 function updateFelder(){
	new Ajax.Request("./interface/rme.php?class=Exp&method=getFelder&constructor=-1&parameters='"+$('plugin').value+"'",{ onSuccess: function(transport){
		$('felder').value = transport.responseText;
	}
	});	
}

function makeExport(ExpID){
	document.location.href = "./interface/rme.php?class=Exps&method=getExport&constructor=&parameters='"+ExpID+"'";
	/*new Ajax.Request("./interface/rme.php?class=Exps&method=getExport&constructor=&parameters='"+ExpID+"'",{ onSuccess: function(transport){
		
	}
	});	*/
}