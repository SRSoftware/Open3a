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

var Textbaustein = {
	katChange: function(possible){
		var cName = null;
		var form = $('editTextbausteinGUI');
		
		if(form.KategorieID.value == 1 || form.KategorieID.value == 2 || form.KategorieID.value == 3 || form.KategorieID.value == 41 || form.KategorieID.value == 42){

			for (i = 0; i < possible.length; i++){
				cName = 'is'+possible[i]+'Standard';
				if(form.elements[cName]) form.elements[cName].parentNode.parentNode.style.display = '';
			}

			form.isMStandard.parentNode.parentNode.style.display = 'none';
			form.isKatDefault.parentNode.parentNode.style.display = 'none';
			
			if(form.KategorieID.value == 41 || form.KategorieID.value == 42)
				form.isMStandard.parentNode.parentNode.style.display = '';
		}
		
		else if(form.KategorieID.value == 0){
			for (i = 0; i < possible.length; i++){
				cName = 'is'+possible[i]+'Standard';
				if(form.elements[cName]) form.elements[cName].parentNode.parentNode.style.display = 'none';
			}

			form.isMStandard.parentNode.parentNode.style.display = 'none';
			form.isKatDefault.parentNode.parentNode.style.display = 'none';
		}

		else {//(form.KategorieID.value == 31 || form.KategorieID.value == 32 || form.KategorieID.value == 33){

			for (i = 0; i < possible.length; i++){
				cName = 'is'+possible[i]+'Standard';
				if(form.elements[cName]) form.elements[cName].parentNode.parentNode.style.display = 'none';
			}
			
			form.isMStandard.parentNode.parentNode.style.display = 'none';
			form.isKatDefault.parentNode.parentNode.style.display = '';
			
			$("TBType").update(form.KategorieID.options[form.KategorieID.selectedIndex].text);
		}
		
		contentManager.toggleFormFields(form.KategorieID.value > 100 || form.KategorieID.value == 42 ? "show" : "hide", ["betreff"], "editTextbausteinGUI");
		
		Textbaustein.updateTBVariables();
	},

	updateTBVariables: function() {
		contentManager.rmePCR("Textbausteine", "", "getTBVariables", $('editTextbausteinGUI').KategorieID.value, function(transport){
			$j('#tinyMCEVars').html(transport.responseText);
			return;
			/*
			if(transport.responseText == "nil") {
				//new Effect.Fade("TBVarsContainer");
				if($('tinyMCEVars'))
					$('tinyMCEVars').update("Keine Variablen verfügbar");
				return;
			}

			var Vars = transport.responseText.split(";");
			var text = "";
			for(i = 0; i<Vars.length;i++)
			text = text + "{"+Vars[i]+"}<br />";
		
			if($('tinyMCEVars')){
				$('tinyMCEVars').update(text);
				$j("#tinyMCEVarsDescription").html('Sie können folgende Variablen in Ihrem Text verwenden (bitte beachen Sie Groß- und Kleinschreibung):');
			}*/
		});
		

	}
}