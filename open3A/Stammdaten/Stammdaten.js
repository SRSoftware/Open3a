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
 function stammdaten_form_updater() {
	if($("template").value == "StPDFBriefkopf" || $("template").value == "own") {
		$('firmaLang').parentNode.parentNode.style.display = '';
		//$('vorname').parentNode.parentNode.style.display = '';
		//$('nachname').parentNode.parentNode.style.display = '';
		$('inhaber').parentNode.parentNode.style.display = '';
		//$('strasse').parentNode.parentNode.style.display = '';
		//$('plz').parentNode.parentNode.style.display = '';
		//$('ort').parentNode.parentNode.style.display = '';
		$('telefon').parentNode.parentNode.style.display = '';
		$('fax').parentNode.parentNode.style.display = '';
		//$('nr').parentNode.parentNode.style.display = '';
		$('land').parentNode.parentNode.style.display = '';
		$('mobil').parentNode.parentNode.style.display = '';
		$('email').parentNode.parentNode.style.display = '';
		$('internet').parentNode.parentNode.style.display = '';
		$('ustidnr').parentNode.parentNode.style.display = '';
		$('bank').parentNode.parentNode.style.display = '';
		$('blz').parentNode.parentNode.style.display = '';
		$('ktonr').parentNode.parentNode.style.display = '';
		$('SWIFTBIC').parentNode.parentNode.style.display = '';
		$('IBAN').parentNode.parentNode.style.display = '';
	}
	
	if($("template").value == "own") {
		$('ownTemplate').parentNode.parentNode.style.display = '';
		if($('ownTemplatePrint')) $('ownTemplatePrint').parentNode.parentNode.style.display = '';
		if($('ownTemplateEmail')) $('ownTemplateEmail').parentNode.parentNode.style.display = '';
	} else {
		$('ownTemplate').parentNode.parentNode.style.display = 'none';
		if($('ownTemplatePrint')) $('ownTemplatePrint').parentNode.parentNode.style.display = 'none';
		if($('ownTemplateEmail')) $('ownTemplateEmail').parentNode.parentNode.style.display = 'none';
	}
	
	if($("template").value == "StPDFBriefkopfOL") {
		$('firmaLang').parentNode.parentNode.style.display = 'none';
		//$('firmaLang').parentNode.parentNode.style.display = 'none';
		//$('vorname').parentNode.parentNode.style.display = 'none';
		//$('nachname').parentNode.parentNode.style.display = 'none';
		$('inhaber').parentNode.parentNode.style.display = 'none';
		//$('strasse').parentNode.parentNode.style.display = 'none';
		//$('plz').parentNode.parentNode.style.display = 'none';
		//$('ort').parentNode.parentNode.style.display = 'none';
		$('telefon').parentNode.parentNode.style.display = 'none';
		$('fax').parentNode.parentNode.style.display = 'none';
		//$('nr').parentNode.parentNode.style.display = 'none';
		$('land').parentNode.parentNode.style.display = 'none';
		$('mobil').parentNode.parentNode.style.display = 'none';
		$('email').parentNode.parentNode.style.display = 'none';
		$('internet').parentNode.parentNode.style.display = 'none';
		$('ustidnr').parentNode.parentNode.style.display = 'none';
		$('bank').parentNode.parentNode.style.display = 'none';
		$('blz').parentNode.parentNode.style.display = 'none';
		$('ktonr').parentNode.parentNode.style.display = 'none';
		$('SWIFTBIC').parentNode.parentNode.style.display = 'none';
		$('IBAN').parentNode.parentNode.style.display = 'none';
	}
}
