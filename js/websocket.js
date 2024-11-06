// This file contains every necessary websocket, ban element, drag & drop function and more for the team pages to work properly
var executeOnlyOnce = true;
function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
      return;
    }
    document.addEventListener('DOMContentLoaded', fn);
  }
  
const wfs = new WebSocket('wss://wfsocket.dasnerdwork.net/');

wfs.onopen = (event) => { // Do this on client opening the webpage
    if (document.getElementById("highlighter") != null) {
        var name = document.getElementById("highlighter").innerText
    } else {
        var name = "";
    }
    let sendInfo =  {
        teamid: "123",
        name: name,
        request: "firstConnect"
    };
    wfs.send(JSON.stringify(sendInfo))
};

wfs.onmessage = (event) => { // Do this when the WFS-Server sends a message to client
    if(Array.from(event.data)[0] == "{"){
        var messageAsJson = JSON.parse(event.data);
        // console.log(messageAsJson);
        if(!messageAsJson.hasOwnProperty("SuggestedBans")){
            if(messageAsJson.status == "ElementAlreadyInArray"){
                var d = new Date();
                alert("[" + d.toLocaleTimeString() + "] Dieser Champion wurde bereits ausgewählt.\n");
            } else if (messageAsJson.status == "WarframeData") {
                console.log(messageAsJson.data);
            } else if(messageAsJson.status == "MaximumElementsExceeded"){
                var d = new Date();
                alert("[" + d.toLocaleTimeString() + "] Die maximale Anzahl an ausgewählten Champions wurde erreicht.\n");
            } else if(messageAsJson.status == "CodeInjectionDetected"){
                var d = new Date();
                alert("[" + d.toLocaleTimeString() + "] WARNUNG: Dieser Code Injection Versuch wurde geloggt und dem Administrator mitgeteilt.\n");
            } else if(messageAsJson.status == "InvalidTeamID"){
                var d = new Date();
                alert("[" + d.toLocaleTimeString() + "] Die Anfrage für diese Team ID ist nicht gültig.\n");
            } else if (messageAsJson.status == "FileDidNotExist") {
                window.location.reload();
            } else if (messageAsJson.status == "Update") {
                // if update
            } else if (messageAsJson.status == "FirstConnect") {
                if (document.getElementById("highlighter") != null) {
                    let nameElement = document.getElementById("highlighter");
                    nameElement.classList.add("underline","text-"+messageAsJson.color+"/100");
                    // document.getElementById("highlighterAfter") <- // insert before this element
                } else {
                    const newParentDiv = document.createElement("div");
                    const newImg = document.createElement("img");
                    const newSpan = document.createElement("span");
                    const newP = document.createElement("p");
                    // newParentDiv.classList.add("flex","justify-center","items-center","px-4");
                    newParentDiv.style.marginTop = "-13px";
                    switch (messageAsJson.name){
                        case "Krug": case "Wolf":
                            newParentDiv.style.marginLeft = "-5.75rem";
                            break;
                        case "Nashor": case "Herald": case "Minion": case "Raptor": case "Gromp":
                            newParentDiv.style.marginLeft = "-6.75rem";
                            break;
                        case "Sentinel": case "Scuttler":
                            newParentDiv.style.marginLeft = "-7rem";
                            break;
                        case "Brambleback":
                            newParentDiv.style.marginLeft = "-10rem";
                            break;
                        default:
                            newParentDiv.style.marginLeft = "-9rem";
                    }
                    newParentDiv.setAttribute("onmouseover", "showIdentityNotice(true)");
                    newParentDiv.setAttribute("onmouseout", "showIdentityNotice(false)");
                    newSpan.setAttribute("onmouseover", "showIdentityNotice(true)");
                    newSpan.setAttribute("onmouseout", "showIdentityNotice(false)");
                    newParentDiv.classList.add("z-20","w-40","h-8");
                    getVersionedUrl("/clashapp/data/misc/monsters/"+messageAsJson.name.toLowerCase()+".avif").then(versionedPath => {
                        newImg.src = versionedPath;
                    });
                    newImg.width = "32";
                    newImg.height = "32";
                    newImg.alt = "An icon of a random monster from League of Legends";
                    newImg.classList.add("align-middle","mr-2.5","no-underline","inline-flex");
                    newP.id = "highlighter";
                    newP.classList.add("inline", "underline","decoration-2","text-"+messageAsJson.color+"/100");
                    newP.style.textDecorationSkipInk = "none";
                    newSpan.classList.add("text-white");
                    newSpan.innerText = messageAsJson.name;
                    newP.appendChild(newSpan);
                    newParentDiv.appendChild(newImg);
                    newParentDiv.appendChild(newP);
                    document.getElementById("highlighterAfter").insertBefore(newParentDiv, null);
                    const identityNotice = document.getElementById("identityNotice");
                    identityNotice.setAttribute("onmouseover", "showIdentityNotice(true)");
                    identityNotice.setAttribute("onmouseout", "showIdentityNotice(false)");
                }
            } else if (messageAsJson.status == "Message"){
                if((messageAsJson.message == "added %1.") || (messageAsJson.message == "removed %1.")){
                    addCustomHistoryMessage(messageAsJson.message, messageAsJson.name, messageAsJson.color, messageAsJson.champ);
                } else if(messageAsJson.message == "swapped %1 with %2.") {
                    addCustomHistoryMessage(messageAsJson.message, messageAsJson.name, messageAsJson.color, messageAsJson.champ1, messageAsJson.champ2);
                } else {
                    addCustomHistoryMessage(messageAsJson.message, messageAsJson.name, messageAsJson.color);
                }
            } else if (messageAsJson.status == "Lock") {
                addCustomHistoryMessage(messageAsJson.message, messageAsJson.name, messageAsJson.color, messageAsJson.champ);
                lockSelectedBan(document.getElementById('selectedBans').children[messageAsJson.index], false);
            } else if (messageAsJson.status == "Unlock") {
                addCustomHistoryMessage(messageAsJson.message, messageAsJson.name, messageAsJson.color, messageAsJson.champ);
                unlockSelectedBan(document.getElementById('selectedBans').children[messageAsJson.index], false);
            }
        }
    } else {
        // addHistoryMessage(event.data);
    }
}

wfs.onclose = (event) => { // Do this when the WFS-Server stops
    clearTimeout(this.pingTimeout);
}

function getVersionedUrl(url) {
    return fetch(url)
        .then(response => {
            const lastModified = response.headers.get('Last-Modified');
            return lastModified ? new Date(lastModified).getTime() : null;
        })
        .then(timestamp => {
            return timestamp !== null ? url + "?version=" + timestamp : url;
        })
        .catch(error => {
            console.error("Error fetching or extracting Last-Modified:", error);
            return url; // Return the original URL in case of an error
        });
}

function openModal() {
    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

function submitName() {
    const name = document.getElementById('name-input').value;
    console.log("Entered name:", name);
    closeModal(); // Close modal after submit
}
