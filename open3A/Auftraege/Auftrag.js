/**
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

var AuftraegeMessages = {
	E001: "Sie haben keine Berechtigung, eine Rechnung als bezahlt zu markieren!",
	E002: "Keine als aktiv markierten Stammdaten gefunden.",
	
	E003: "Fehler beim E-Mail-Versand!",
	E004: "Keine Kunden-E-Mail eingetragen!",
	E005: "Keine E-Mail-Adresse in den Stammdaten eingetragen!",
	
	E010: "Bitte geben Sie Benutzername und Passwort für den Signaturanbieter an.\nKlicken Sie dazu auf das Einstellungen-Symbol neben dem signieren-Knopf.",
	E011: function(message) {return message;},
	E012: function(message) {return "pixelLetter Antwortet: \n"+message;},
	E013: "Es wurde keine E-Mail-Adresse beim Kunden angegeben",
	E014: "Bitte überprüfen Sie die Absender-Adresse des Benutzers und in den Stammdaten",
	E015: "Bitte überprüfen Sie die E-Mail-Textbausteine. Eventuell ist kein Textbaustein als Standard markiert. Überprüfen Sie auch die Einstellungen im Kontextmenü des Symbols rechts neben 'per E-Mail verschicken.'",
	
	E016: "Kundennummer existiert nicht!",

	E017: "Dieser Typ mit dieser Nummer existiert nicht!",
	E018: "Diese Nummer wurde mehr als einmal gefunden!",
	
	A001: "Dieser Beleg kann nicht gelöscht werden, es sind noch Posten eingetragen!",
	A002: "Dieser Auftrag kann nicht gelöscht werden, es sind noch Belege eingetragen!",

	A003: "Dieser Posten kann nicht gelöscht werden, der Beleg wurde gesperrt!",
	A004: "Es können keine Posten mehr hinzugefügt werden, der Beleg wurde gesperrt!",
	A005: "Es können keine Posten mehr bearbeitet werden, dieser Beleg wurde gesperrt!",
	A006: "Dieser Beleg kann nicht mehr bearbeitet werden!",
	
	M001: "E-Mail wurde gesendet",
	M002: "Posten kopiert",
	
	C001: "PDF per Mail verschicken?",
	
	T001: "als bezahlt markieren"
}

var Auftrag = {
	newestID: null,
	newestGRLBMID: null,
	currentSection: null,
	
	checkBestand: function(t){
		if(t.responseData.bestand && t.responseData.bestand != "")
			Popup.load("Bestandsprüfung", "mArtikel", -1, "checkBestand", t.responseData.bestand);
	},
	
	addArtikel: function(grlbmID, artikelID, onSuccessFunction){
		var arg = arguments;
		
		contentManager.rmePCR("GRLBM", grlbmID, "getPostenCopy", [artikelID], function(t) {
			Auftrag.reloadBeleg(grlbmID, onSuccessFunction);
			
			Auftrag.checkBestand(t);
		});
	},

	reloadBeleg: function(grlbmID, onSuccessFunction){
		var scrolled = $j('#PostenSortableContainer').scrollTop();
		contentManager.loadFrame('subframe', 'GRLBM', grlbmID, 0, "", function(){
			$j('#PostenSortableContainer').scrollTop(scrolled);
			if(typeof onSuccessFunction == "function")
				onSuccessFunction();
		});
	},

	reWidth: function(){
		if(!$j('.AuftragBelegContent').length)
			return;
		
		var width = contentManager.maxWidth() - $j('#contentRight').outerWidth() - $j('#contentRight .sideTableLeft ').outerWidth();
		if(width > 840)
			width = 840;
		
		if(width < 410)
			width = 410;
		
		$j('#AuftragBeleg').css('width', width+'px');
		$j('.AuftragBelegContent').css('width', (width - 20)+'px');
	},

	updatePositionsNummern: function(data){
		$j.each(data, function(k, v){
			$j('#posNr'+k).html(v);
		});
	},

	updateNettoBrutto: function(GRLBMID, PostenID){
		contentManager.rmePCR("mPosten", "-1", "getSummen", [GRLBMID, PostenID], function(transport){$("belegSummen").update(transport.responseText); Auftrag.reWidth();});
	},

	showTeilzahlung: function(cb){
		if(!$('isTeilzahlung')) return;
		
		if($('isTeilzahlung').checked) {
			$('markAsPayedSkonto').style.display = 'none';
			$('GRLBMTeilzahlungenBetragCell').style.display = '';
		} else {
			$('markAsPayedSkonto').style.display = '';
			$('GRLBMTeilzahlungenBetragCell').style.display = 'none';
		}
	},

	showPDF: function(){
		 windowWithRme('Auftrag', Auftrag.newestID, 'getGRLBMPDF', Array('false','',Auftrag.newestGRLBMID), '_Brief;templateType:PDF', 'window');
	},

	workflowCreateNewRechnungWithAdresse: function(){
		Workflow.setSubSteps([Auftrag.createEmpty, Auftrag.selectAdresse]);
		Workflow.runSubSteps();
		
		Aspect.registerOnLoadFrame("contentLeft", "Auftrag", false, function(){
			Auftrag.createGRLBM(Auftrag.newestID, "Auftrag", "R");
			Aspect.unregisterPointCut("loaded", "contentManager.loadFrame");
			Workflow.activateNextStep();
		});
	},
	
	workflowSelectArtikel: function(){
		contentManager.backupFrame('contentRight','selectionOverlay');
		contentManager.customSelection('contentRight', Auftrag.newestGRLBMID, 'mArtikel', 'Auftrag.addArtikel');
		
		Aspect.registerOnLoadFrame("contentRight", "Auftraege", false, function(){
			Aspect.unregisterPointCut("loaded", "contentManager.loadFrame");
			Workflow.activateNextStep();
		});
	},

	selectAdresse: function(onSuccessFunction){
		contentManager.loadFrame('contentRight','Adressen', -1, 0,'AdressenGUI;selectionMode:singleSelection,Auftrag,'+Auftrag.newestID+',getAdresseCopy,Auftraege,contentLeft,Auftrag,'+Auftrag.newestID+'', onSuccessFunction);
	},

	createEmpty: function(onSuccessFunction, addBeleg, AdresseID){
		if(typeof addBeleg == "undefined")
			addBeleg = "";
		
		if(typeof AdresseID == "undefined")
			AdresseID = "";
		
		contentManager.rmePCR("Auftraege", "-1", "createEmpty", [addBeleg, AdresseID], function(transport){
			Auftrag.newestID = transport.responseText; 
			
			if(typeof onSuccessFunction == "function")
				onSuccessFunction(transport);
			
			/*if(!$('AddAuftragButton')) return; 
			
			$('AddAuftragButton').style.display = 'none'; 
			$('AddAdresseButton').style.display = ''; 
			$('Add1xAdresseButton').style.display = '';*/
		});
		//rmeP("Auftraege", "-1", "createEmpty", "", "Auftrag.newestID = transport.responseText; if(!$('AddAuftragButton')) return; $('AddAuftragButton').style.display = 'none'; $('AddAdresseButton').style.display = ''; $('Add1xAdresseButton').style.display = '';");
	},
	
	createOrder: function(nothing, LieferantID){
		contentManager.rmePCR("mLBestellung", -1, "createNew", [LieferantID, "O"], function(transport){ 
			contentManager.loadFrame("contentLeft", "LBestellung", transport.responseText);
			contentManager.restoreFrame('contentRight','selectionOverlay');
		});
	},
	
	createPriceRequest: function(nothing, LieferantID){
		contentManager.rmePCR("mLBestellung", -1, "createNew", [LieferantID, "P"], function(transport){ 
			contentManager.loadFrame("contentLeft", "LBestellung", transport.responseText);
			contentManager.restoreFrame('contentRight','selectionOverlay');
		});
	},
	
	addFile: function(GRLBMID, fileID){
		rmeP("GRLBM", GRLBMID, "addFile", fileID, "contentManager.loadFrame('subframe', 'GRLBM', "+GRLBMID+", 0)");
	},
	
	setRechnungPayed: function(image, rechnungID){
		if(image.src.search(/notok/) > -1)
			phynxContextMenu.start(image, 'GRLBM','setPayed:'+image.id+":"+rechnungID, AuftraegeMessages.T001+':');

		else
			contentManager.rmePCR("GRLBM", rechnungID, "setPayed", new Array('false'), function() {contentManager.updateLine('', rechnungID, 'Uebersicht');$('contentLeft').update('');});
	},

	nowSetRechnungPayed: function(imageID, rechnungID){
		contentManager.rmePCR("GRLBM", rechnungID, "setPayed", new Array('true', $('withSkonto').value, $('withDate').value, (($('isTeilzahlung') && $('isTeilzahlung').checked) ? "true" : "false"), $('isTeilzahlung') ? $('GRLBMTeilzahlungenBetrag').value : ""), function() {contentManager.updateLine('', rechnungID, 'Uebersicht');$('contentLeft').update('');});
		phynxContextMenu.stop();
	},

	copyPostenFrom: function(copyFromId, copyToId, copyToType, targetFrame){
		new Ajax.Request("./interface/rme.php?class=GRLBM&constructor="+copyToId+"&method=copyPostenFrom&parameters=\'"+copyFromId+"\'", {
		method: 'get',
		onSuccess: function(transport) {
			if(checkResponse(transport))
				contentManager.loadFrame(targetFrame, 'GRLBM', copyToId);
		}});
	},

	copyPostenByTypeAndNumber: function(copyFromNumber, copyFromType, copyToId, targetFrame){
		new Ajax.Request("./interface/rme.php?class=GRLBM&constructor="+copyToId+"&method=copyPostenByTypeAndNumber&parameters=\'"+copyFromNumber+"\','"+copyFromType+"'", {
		method: 'get',
		onSuccess: function(transport) {
			if(checkResponse(transport))
				contentManager.loadFrame(targetFrame, 'GRLBM', copyToId);
		}});
		
		phynxContextMenu.stop();
	},

	windowMail: function(Aid, GRLBMID, mode, AnsprechpartnerID){
		if(typeof AnsprechpartnerID == "undefined")
			AnsprechpartnerID = "0";

		rmeP("Auftrag", Aid, "getViaEMailWindow", [GRLBMID, mode, AnsprechpartnerID], "if(checkResponse(transport)) { Popup.create("+Aid+", 'EMailPreview', 'E-Mail Vorschau'); Popup.update(transport, "+Aid+", 'EMailPreview'); }");

	},

	directMail: function(Aid, GRLBMID, recipient, subject, body){
		if(!confirm(AuftraegeMessages.C001)) return;
		
		contentManager.rmePCR("Auftrag", Aid, "sendViaEmail", [GRLBMID, recipient, subject, body], function(){
			if(lastLoadedLeftPlugin == 'Bestellung')
				contentManager.reloadFrame('contentLeft');
			
			if($('GRLBM1xEMail'))
				$('GRLBM1xEMail').value = '';
			
			$j('#sendViaEmailButton').addClass('confirm');
		}
		);

	},

	plSign: function(id, GRLBMID, recipient, subject, body){
		if(!confirm('Soll diese Rechnung qualifiziert digital signiert werden?\nAchtung: dadurch entstehen Kosten.')) return;
		//rmeP('Auftrag',id,'signLetter',[GRLBMID, recipient, subject, body],'checkResponse(transport); if($("GRLBM1xEMail")) $("GRLBM1xEMail").value = "";')
		contentManager.rmePCR('Auftrag',id,'signLetter',[GRLBMID, recipient, subject, body], function(){ 
			if($("GRLBM1xEMail"))
				$("GRLBM1xEMail").value = "";
			
			$j('#buttonPixelLettered').addClass('confirm');
		});
	},

	addToMultiPrint: function(id){
		rmeP("multiPrintBasket", "", "emptyList", "", "", "_multiPrintBasketGUI;_ids:"+id);
		if($('subID'+id)) $('subID'+id).style.display = 'block';
		if($('addID'+id)) $('addID'+id).style.display = 'none';
	},

	subFromMultiPrint: function(id){
		rmeP("multiPrintBasket", "", "emptyList", "", "", "_multiPrintBasketGUI;-ids:"+id);
		if($('subID'+id)) $('subID'+id).style.display = 'none';
		if($('addID'+id)) $('addID'+id).style.display = 'block';
	},
	
	createGRLBM: function(classId, template, type){
		if($(type+'Button').name == "2"){
			contentManager.loadFrame('subframe', 'mGRLBM', -1, 0, 'mGRLBMGUI;type:'+type+';AuftragID:'+classId);
			return;
		}

		contentManager.rmePCR(template, classId, "createGRLBM", [type, "1"], function(transport) {
			if(!checkResponse(transport)) return;

			Auftrag.newestGRLBMID = transport.responseText;
			
			$(type+'Button').name = "2";
			$(type+'Button').value = $(type+'Button').value.replace(/erstellen/,"anzeigen");
			$(type+'Button').setAttribute("class", "backgroundColor3 bigButton");

			contentManager.loadFrame('subframe', 'mGRLBM', -1, 0, 'mGRLBMGUI;type:'+type+';AuftragID:'+classId);
			contentManager.reloadFrame("contentRight");
		});
		
		/*new Ajax.Request("./interface/rme.php?class="+template+"&constructor="+classId+"&method=createGRLBM&rand="+Math.random()+"&parameters='"+type+"'", {
		method: 'get',
		onSuccess: });*/
	},
	
	highlightSection: function(section){
		if(Auftrag.currentSection != null && !$j('#'+Auftrag.currentSection+"Button").hasClass("backgroundColor0")){
			$j('#'+Auftrag.currentSection+"Button").removeClass("backgroundColor1");
			$j('#'+Auftrag.currentSection+"Button").addClass("backgroundColor3");
		}
		
		$j('#'+section+"Button").removeClass("backgroundColor3");
		$j('#'+section+"Button").addClass("backgroundColor1");
		
		Auftrag.currentSection = section;
	},
			
	availabeTBSelection: function(TBType){
		contentManager.rmePCR("mGRLBM", "-1", "availabeTBSelection", [TBType], function(t){
			
			if($('tinyMCEVars')){
				$('tinyMCEVars').update(t.responseText);
				$j("#tinyMCEVarsDescription").html('Folgende Textbausteine stehen zur Verfügung:');
			}
		});
	},
			
	loadAndUpdateEditedTB: function(TBID){
		contentManager.rmePCR("mGRLBM", "-1", "loadTBForEditor", [TBID], function(t){
			tinymce.activeEditor.selection.setContent(t.responseText);
			//nicEditors.findEditor('nicEditor').setContent(t.responseText);
			///$j('.nicEdit-main').trigger('focus');
		});
	}
		
}

function addToMultiPrintAusAuftrag(id){
	rmeP("multiPrintBasket", "", "emptyList", "", "", "_multiPrintBasketGUI;_ids:"+id);
	//loadFrameV2("","","_multiPrintBasketGUI;_ids:"+id);
	if($('GRLBMAddToMultiPrintList')) $('GRLBMAddToMultiPrintList').style.display = "none";
	if($('GRLBMSubFromMultiPrintList')) $('GRLBMSubFromMultiPrintList').style.display = "";
	if($('subID'+id)) $('subID'+id).style.display = 'block';
	if($('addID'+id)) $('addID'+id).style.display = 'none';
}

function subFromMultiPrintAusAuftrag(id){
	//loadFrameV2("","","_multiPrintBasketGUI;-ids:"+id);
	rmeP("multiPrintBasket", "", "emptyList", "", "", "_multiPrintBasketGUI;-ids:"+id);
	if($('GRLBMAddToMultiPrintList')) $('GRLBMAddToMultiPrintList').style.display = "";
	if($('GRLBMSubFromMultiPrintList')) $('GRLBMSubFromMultiPrintList').style.display = "none";
	if($('subID'+id)) $('subID'+id).style.display = 'none';
	if($('addID'+id)) $('addID'+id).style.display = 'block';
}

function makeFloat(fieldId){
	return parseFloat($(fieldId).value.replace(".","").replace(",","."));
}

function makeString(floatt){
	var string = ((Math.round(floatt * 100) / 100)+"").replace(".",",");
	if(string.indexOf(",") == -1) string += ",00";
	if(string.length - string.indexOf(",") == 2) string += "0";
	return string;
}

function calculateKalk(){
	var list = new Array();
	var es = $("mPostenForm").elements;
	
	var EK1Sum = 0;
	var EK2Sum = 0;
	var VKSum = 0;

	for(i = 0;i < es.length;i++) {
		if(es[i].id.indexOf("nameID") >= 0)
			list.push(es[i].id.replace("nameID",""));
	}
	
	for(i = 0;i < list.length;i++){
		if($('PostenDisplayD'+list[i]+"1").style.display == "none") continue;
		VKSum += makeFloat('mengeID'+list[i]) * makeFloat('preisID'+list[i]);
		if($('EK1ID'+list[i])) EK1Sum += makeFloat('mengeID'+list[i]) * makeFloat('EK1ID'+list[i]);
		if($('EK2ID'+list[i])) EK2Sum += makeFloat('mengeID'+list[i]) * makeFloat('EK2ID'+list[i]);
	}
	
	if($('gesamtVK')) $('gesamtVK').update(makeString(VKSum)+"€");
	if($('gesamtEK1')) $('gesamtEK1').update(makeString(EK1Sum)+"€");
	if($('gesamtEK2')) $('gesamtEK2').update(makeString(EK2Sum)+"€");
	
	if($('rabatt')) var rabatt = VKSum * makeFloat("rabatt") / 100;
	if($('rabattInW'))
		if(makeFloat('rabattInW') == 0) VKSum = VKSum - rabatt;
		else VKSum = VKSum - makeFloat('rabattInW');
		
	if($('gesamtVK2')) $('gesamtVK2').update(makeString(VKSum)+"€");
	if($('rabattInP')) $('rabattInP').update(makeString(rabatt)+"€");
	if($('rabattInW2')) $('rabattInW2').update(makeString(makeFloat("rabattInW"))+"€");
	
	if($('VKEK1')) $('VKEK1').update(makeString(VKSum - EK1Sum)+"€");
	if($('VKEK2')) $('VKEK2').update(makeString(VKSum - EK2Sum)+"€");
	if($('leasingrate2')) $('leasingrate2').update(makeString(VKSum * makeFloat("leasingrate") / 100)+"€");
	
	if($('servicepolice1K')) $('servicepolice1K').update(makeString(EK2Sum * makeFloat("servicepolice1") / 100)+"€");
	if($('servicepolice2K')) $('servicepolice2K').update(makeString(EK2Sum * makeFloat("servicepolice2") / 100)+"€");
	if($('servicepolice3K')) $('servicepolice3K').update(makeString(EK2Sum * makeFloat("servicepolice3") / 100)+"€");

}

$j(window).resize(function() {
	Auftrag.reWidth();
});