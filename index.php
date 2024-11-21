<?php if (session_status() === PHP_SESSION_NONE) session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '
<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta name="viewport" content="width=device-width">
        <meta name="description" content="Offizielle DasNerdwork.net Website für Warframe Minispiele">
        <title>Warframe – DasNerdwork.net</title>
        <link id="favicon" rel="shortcut icon" href="https://warframe.dasnerdwork.net/data/misc/favicon.ico">
        <link rel="stylesheet" href="/output.css?version='.md5_file("/hdd1/warframe/css/output.css").'">
        <script type="text/javascript" async src="/main.min.js?version='.md5_file("/hdd1/clashapp/js/main.min.js").'"></script>
    </head>
';

// echo '<script type="text/javascript" async src="/websocket.js?version='.md5_file("/hdd1/warframe/js/websocket.js").'"></script>

include_once('/hdd1/clashapp/src/functions.php');
include_once('/hdd1/warframe/lang/translate.php');
require_once '/hdd1/clashapp/db/clash-db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// print_r($_SESSION);

if(isset($_GET['join'])){ // Used for invite codes via ?join=roomCode
    $sanitizedRoom = filter_input(INPUT_GET, 'join', FILTER_SANITIZE_NUMBER_INT);
    if ($sanitizedRoom !== false) {
        setcookie('roomCode', $sanitizedRoom, time()+86400, '/');
        $_COOKIE['roomCode'] = $sanitizedRoom;
        echo '<script>window.location.href = "/";</script>';
    }
} 
if(isset($_COOKIE['playername'])){
    $_SESSION['user']['name'] = $_COOKIE['playername'];
}

?>
<div class="min-h-[calc(100vh_-_90px)]">
<header class="bg-dark">
	<div class="flex h-16">
		<div class="w-44 -mr-2.5 float-left p-2">
			<a href="/" class="block no-underline text-white align-middle w-full">
				<!-- <img src="/clashapp/data/misc/webp/logo.avif?version= <?= md5_file("/hdd1/clashapp/data/misc/webp/logo.avif") ?>" alt="The main logo of the website" width="160" height="44"> -->
			</a>
		</div>
        <div class="absolute right-0 flex h-16">
            <?php if(isset($_SESSION['user']['puuid'])){ // If there is currently a user logged in && the user has a connected league account
                if($mdb->getPlayerByPUUID($_SESSION['user']['puuid'])["success"]){
                $headerJsonString = json_encode($mdb->getPlayerByPUUID($_SESSION['user']['puuid'])["data"]);
                $headerJson = json_decode($headerJsonString, true);
                }
                $dataName = isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : '';
            ?>
            <div class="flex justify-center items-center px-4 mt-[3px]">
                <?php echo '<a class="group" href="https://clashscout.com/profile/'.strtolower($headerJson["PlayerData"]["GameName"]).'/'.strtolower($headerJson["PlayerData"]["Tag"]).'">';
                      echo '<img width="32" height="32" src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$headerJson["PlayerData"]["Icon"].'.avif?version='.md5_file('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$headerJson["PlayerData"]["Icon"].'.avif').'" class="align-middle mr-2.5 no-underline inline-flex" alt="A custom profile icon of a player">';
                      echo '<p id="highlighter" data-username="'.$dataName.'" class="inline decoration-2  group-hover:underline group-hover:text-[#fff]" style="text-decoration-skip-ink: none;"><span class="text-white">'.$headerJson["PlayerData"]["GameName"].'</span></p><span class="bg-searchtitle px-1 rounded ml-1 text-sm decoration-2 group-hover:text-[#fff] text-[#9ea4bd]">#'.$headerJson["PlayerData"]["Tag"].'</span></a>'; ?>
            </div>
            <?php } else if(isset($_SESSION['user']['username'])){ ?>
            <div class="flex justify-center items-center px-4 mt-[3px]">
                <a href="https://clashscout.com/settings">
                    <img width="32" height="32" src="/clashapp/data/misc/profile-icon.avif?version= <?= md5_file("/hdd1/clashapp/data/misc/profile-icon.avif") ?>" class="align-middle mr-2.5 no-underline inline-flex" alt="The sandard profile icon if no league of legends account is connected">
                    <?php echo '<span id="highlighter" class="hover:text-[#fff] hover:underline decoration-2 active:text-[#ddd]" style="text-decoration-skip-ink: none;">'.$_SESSION['user']['username'].'</span></a>'; ?> 
            </div>
            <?php } ?>
            <div class="w-40 bg-black/75 text-white text-center text-xs rounded-lg py-2 absolute px-3 -ml-[116px] mt-[56px] transition-opacity hidden z-30" id="identityNotice">
                <?=__('This is your current identity and color for others. To customize it please')?> <a href='/login' class='underline'><?=__('login')?></a>.
                <svg class="absolute text-black h-4 w-full left-0 top-full -mt-24 rotate-180" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve"><polygon class="fill-current" points="0,0 127.5,127.5 255,0"></polygon></svg>
            </div>
            <div id="highlighterAfter">
                <select id="language-selector" aria-label="Language Selector" class="text-center h-8 w-28 align-middle mr-2.5 ml-2.5 text-base translate-y-2/4 bg-[#eee] text-black active:bg-[#ccc] focus:outline-none border-none" onchange="selectLang(this)">
                    <option value="en_US" <?= (!isset($_COOKIE["lang"]) || $_COOKIE["lang"] == "en_US") ? "selected disabled hidden" : ""; ?>>English</option>
                    <option value="de_DE" <?= (isset($_COOKIE["lang"]) && $_COOKIE["lang"] == "de_DE") ? "selected disabled hidden" : ""; ?>>Deutsch</option>
                </select>
            </div>
            <div id="settings-button" class="flex items-center ml-2 mr-4 cursor-pointer" onclick="document.getElementById('modal').classList.contains('hidden') ? document.getElementById('modal').classList.replace('hidden', 'flex') : document.getElementById('modal').classList.replace('flex', 'hidden')">
                <img src="/data/misc/settings-wheel.avif?version=<?= md5_file("/hdd1/warframe/data/misc/settings-wheel.avif") ?>" width="20" height="20" alt="A settings wheel icon which looks like a gear" title="<?= __("Settings") ?>">
            </div>
            <div id="modal" class="hidden fixed items-center justify-center z-50 right-4 top-20">
                <div class="bg-dark p-6 rounded-lg rounded-tr-none shadow-lg w-80">
                    <label for="name-input"><?=__('Custom Name');?></label>
                    <input type="text" id="name-input" placeholder="<?=__('ExampleName123');?>" class="autofill:text-black text-black p-2 mt-2 mb-4 w-full"
                           onkeydown="if (event.key === 'Enter') { setCookie('playername', document.getElementById('name-input').value); location.reload(); }">
                    <div class="flex justify-end space-x-2">
                        <button onclick="document.getElementById('modal').classList.replace('flex', 'hidden')"><?=__('Cancel');?></button>
                        <button onclick="setCookie('playername', document.getElementById('name-input').value); location.reload();" class="bg-tag-navy text-white px-4 py-2"><?=__('Ready');?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<?php
$championData = json_decode(file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/en_US/champion.json'), true);
$championKeys = array(); // Initialize an empty array
foreach ($championData['data'] as $key => $champion) {
    $originalName = $champion['name'];
    $championKeys[$originalName] = $key;
}
$randomChampionKey = $championKeys[array_rand($championKeys)];
$randomChampion = $championData['data'][$randomChampionKey];
$championName = $randomChampion['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = $_POST['champion_input'];
    $isCorrect = strtolower($userInput) === strtolower($championName);
}
$db = new DB();
?>
<style>
    .correct-border {
        border: 2px solid green !important;
    }
    .incorrect-border {
        border: 2px solid red !important;
    }
    #imageContainer {
        position: relative;
    }
    #fullImage {
        position: absolute;
        opacity: 0;
        transition: none;
    }
    #pixelatedCanvas {
        opacity: 1;
        transition: none;
    }
    .animate-border-color {
        transition: border-color 0.5s ease-out;
    }
    #championName {
        transition: opacity 0.5s ease-in;
    }
    #suggestions {
        position: absolute;
        margin-top: 2.8rem;
        width: 13.45rem;
        color: #000;
        text-align: left;
    }
    .suggestion {
        cursor: pointer;
        padding: 5px;
        border: 1px solid #ccc;
        background-color: #eee;
        margin-top: -1px;
    }
    .suggestion:hover {
        background-color: #ccc;
    }
    .bonus-bar {
        width: 100%;
        height: 1.5rem;
        background-color: green;
        animation: decreaseBonus 10s linear forwards;
        transform-origin: left center;
    }

    @keyframes decreaseBonus {
        to {
            /* More performant than animating `width` */
            transform: scaleX(0);
        }
    }

    .bonus-bar.stopped {
        animation: none;
    }
</style>
<script>
    if (typeof getCookie !== 'function') {
        function getCookie(name) {
            var value = '; ' + document.cookie;
            var parts = value.split('; ' + name + '=');
            if (parts.length === 2) {
                return parts.pop().split(';').shift();
            }
        }
    }
    if (typeof setCookie !== 'function') {
        function setCookie(name, value, time = (new Date(Date.now()+ 86400*1000*365)).toUTCString()) {
            document.cookie = name+"="+value+"; expires="+time+"; path=/";
        }
    }
    let championName = "";
    let fullImageSource = "";
    var ownUsername = "";
    let isFormSubmissionBlocked = false;
    checkAndSetRoomCodeCookie();

    function checkAndSetRoomCodeCookie() {
        const existingRoomCode = getCookie("roomCode");
        if (!existingRoomCode) {
            const newRoomCode = Math.floor(Math.random() * 9000000000) + 1000000000;
            const expirationDate = new Date(Date.now() + 24 * 60 * 60 * 1000); // 24 hours
            setCookie("roomCode", newRoomCode, expirationDate.toUTCString());
        }
    }

    function addChatMessage(name, message, color, arg1 = ''){
        arg1 = arg1 || "";
        const chatContainer = document.getElementById("chatContainer");
        const textMessage = document.createElement("span");
        textMessage.classList.add("text-[#333344]");
        __(message).then(function (result) {
            textMessage.innerHTML = "<span class='text-"+color+"/100'>"+name+"</span> "+result.replace("%1", arg1);
            if (chatContainer.children.length > 0) {
                chatContainer.insertBefore(textMessage, chatContainer.children[0]);
            } else {
                chatContainer.appendChild(textMessage);
            }
        });
    }

    function copyInviteLink(element, text, delay, direction, additionalCSS = '') {
        copyToClipboard(window.location.href + '?join=' + getCookie('roomCode'));
        var tooltipElement = showTooltip(element, text, delay, direction, additionalCSS);
        setTimeout(() => {
            hideTooltip(tooltipElement);
        }, 1000);
    }

    function copyToClipboard(text){
        navigator.clipboard.writeText(text).then(function() {
        }, function(err) {
            console.error('Async: Could not copy text: ', err);
        });
    }

    function showTooltip(element, text, delay, direction, additionalCSS = '') {
        const positions = {
            'top-center': '-ml-20 twok:-mt-24 fullhd:-mt-20',
            'top-right': 'ml-4 -mt-14',
        };

        const timestamp = new Date().getTime(); // Get a unique timestamp

        element.insertAdjacentHTML(
            'beforeend',
            `<div data-tooltip-id="${timestamp}" class="w-auto z-30 bg-opacity-65 bg-black text-white text-center text-xs p-2 rounded-lg absolute ${positions[direction]} hidden ${additionalCSS}"
            id="tooltip">${text}</div>`
        );

        const tooltip = document.getElementById('tooltip');
        setTimeout(() => {
            if (tooltip) {
            tooltip.classList.remove('hidden'); // Show the tooltip after adding it to the DOM
            tooltip.style.opacity = '1'; // Set opacity after a brief delay for the fade-in effect
            }
        }, delay);
        return tooltip;
    }

    function hideTooltip(tooltipParent) {
        var firstTooltip = tooltipParent.parentElement.querySelector('div[data-tooltip-id]');
        if (firstTooltip) {
            firstTooltip.style.opacity = '0';
            firstTooltip.classList.add('hidden');
            firstTooltip.remove();
        }
    }

    const ws = new WebSocket('wss://wfsocket.dasnerdwork.net/');

    ws.onopen = (event) => { // Do this on client opening the webpage
        let highlighterElem = document.getElementById("highlighter");
        if (getCookie("playername") != null) {
            var name = getCookie("playername");
        } else {
            var name = "";
        }
        const pixelChampDifficulty = getCookie("pixelChamp") || "easy";
        let sendInfo =  {
            roomid: getCookie("roomCode"),
            name: name,
            difficulty: pixelChampDifficulty,
            request: "minigames",
            action: "generate"
        };
        ws.send(JSON.stringify(sendInfo))
    };

    ws.onmessage = (event) => { // Do this when the WS-Server sends a message to client
        if(Array.from(event.data)[0] == "{"){
            var messageAsJson = JSON.parse(event.data);
            var userList = document.getElementById("userList");
            if (messageAsJson.status == "RoomJoined") {
                addChatMessage(messageAsJson.name, messageAsJson.message, messageAsJson.color);
                ownUsername = messageAsJson.name;
            } else if (messageAsJson.status == "WarframeData") {
                warframeKeys = {}; // Initialize the keys object
                messageAsJson.data.forEach(item => {
                    warframeKeys[item.name] = item.identifier; // Store it in the warframeKeys
                });
            } else if (messageAsJson.status == "Message") {
                if(messageAsJson.answer){
                    const username = "<?= isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : ''; ?>";
                    if(username != ""){
                        postAjax('../ajax/pixelGuesser.php', { username: username, points: (100+parseInt(messageAsJson.bonuspoints, 10)) }, function(responseText) {
                            if (responseText === 'success') {
                                addChatMessage(messageAsJson.name, messageAsJson.message, messageAsJson.color, messageAsJson.answer);
                                if(ownUsername === messageAsJson.name){
                                    let points = document.getElementById('gamePoints');
                                    points.innerHTML = parseInt(points.innerHTML, 10)+100+parseInt(messageAsJson.bonuspoints, 10);
                                    // Create the pointIndicator element
                                    const pointIndicator = document.createElement('span');
                                    pointIndicator.className = 'text-sm opacity-0 absolute text-[#00FF00] animate-moveUpAndFadeOut';
                                    pointIndicator.textContent = '  +'+(100+parseInt(messageAsJson.bonuspoints, 10));
                                    points.insertAdjacentElement('afterEnd', pointIndicator);
                                    pointIndicator.addEventListener('animationend', function () {
                                        // points.parentElement.removeChild(pointIndicator);
                                    });
                                }
                            } else {
                                console.error('Error adding points');
                            }
                        });
                    } else {
                        addChatMessage(messageAsJson.name, messageAsJson.message, messageAsJson.color, messageAsJson.answer);
                    }
                    // Dynamically update leaderboard points
                    let leaderboardListItems = document.getElementById('leaderboardList').getElementsByTagName('li');
                    for (let i = 0; i < leaderboardListItems.length; i++) {
                        let playersNameDiv = leaderboardListItems[i].getElementsByTagName('div')[0];
                        if (playersNameDiv.textContent.includes(messageAsJson.name)) {
                            let playerPointsSpan = leaderboardListItems[i].getElementsByTagName('span')[0];
                            let currentPoints = parseInt(playerPointsSpan.textContent.match(/\d+/)[0]);
                            playerPointsSpan.textContent = "(" + (currentPoints + 100 + parseInt(messageAsJson.bonuspoints, 10)) + ")";
                            break; // Once the player is found, exit the loop
                        }
                    }
                    // Unveil image and text
                    const startTime = performance.now();
                    const champName = document.getElementById("championName");
                    const fullImage = document.getElementById("fullImage");
                    const pixelatedCanvas = document.getElementById("pixelatedCanvas");
                    champName.innerText = "<?= __("It was: ") ?>"+atob(championName);
                    fullImage.src = fullImageSource.replace("/hdd1/warframe/", '');

                    function revealFullImage(timestamp) {
                        if (!startTime) startTime = timestamp;
                        const elapsedTime = timestamp - startTime;
                        const progress = Math.min(elapsedTime / 1000, 1);
                        fullImage.style.opacity = progress;
                        pixelatedCanvas.style.opacity = 1 - progress;
                        if (progress < 1) {
                            requestAnimationFrame(revealFullImage);
                        } else {
                            fullImage.style.opacity = 1;
                            pixelatedCanvas.style.opacity = 0;
                            champName.style.opacity = 1;
                        }
                    }

                    requestAnimationFrame(revealFullImage);
                } else {
                    addChatMessage(messageAsJson.name, messageAsJson.message, messageAsJson.color);
                }
            } else if (messageAsJson.status == "PlayerListUpdate") {
                let playerList = messageAsJson.players;
                const existingUserLis = Array.from(userList.children[1].children);

                // Remove <li> elements that are not in the current player list
                existingUserLis.forEach(existingUserLi => {
                    const liText = existingUserLi.textContent.trim();
                    if (!playerList.includes(liText) && !liText.includes(ownUsername)) {
                    existingUserLi.remove();
                    }
                });

                // Find and remove the existing (You) entry for your name
                const existingYouLi = existingUserLis.find(li => li.textContent.includes(ownUsername));
                if (existingYouLi) {
                    existingYouLi.remove();
                }

                // Add new <li> elements for players in the current player list
                playerList.forEach(playerName => {
                    const existingUserLi = existingUserLis.find(li => li.textContent.trim() === playerName);
                    if (!existingUserLi) {
                    const userName = document.createElement('li');
                    userName.innerText = playerName;

                    if (playerName === ownUsername) {
                        userName.innerHTML = "<span class='text-"+messageAsJson.colors[playerName]+"/100'>"+playerName+"</span> " + " (<?= __("You") ?>)";
                        userName.classList.add("overflow-hidden", "text-ellipsis", "whitespace-nowrap", "text-white", "font-bold");
                        userList.children[1].insertBefore(userName, userList.children[1].firstChild);
                    } else {
                        userName.innerHTML = "<span class='text-"+messageAsJson.colors[playerName]+"/100'>"+playerName+"</span>";
                        userName.classList.add("overflow-hidden", "text-ellipsis", "whitespace-nowrap", "text-gray");
                        userList.children[1].appendChild(userName);
                    }
                    }
                });
                }
                else if (messageAsJson.status === "PixelateAndGenerate") {
                const expirationDate = new Date(Date.now() + 24 * 60 * 60 * 1000); // 24 hours
                pixelationDifficulty = messageAsJson.pixelationDifficulty;
                image = messageAsJson.image;
                difficultySelector.value = pixelationDifficulty;
                setCookie("pixelChamp", pixelationDifficulty, expirationDate.toUTCString());
                const options = difficultySelector.getElementsByTagName("option");
                for (const option of options) {
                    if(option.value === pixelationDifficulty){
                        option.setAttribute("hidden", "");
                    } else {
                        option.removeAttribute("hidden");
                    }
                }
                resetBonusBar();

                // Save variables
                fullImageSource = atob(messageAsJson.origImage);
                championName = messageAsJson.championName;
                addImageToCanvas(image);
            } else if (messageAsJson.status === "PixelateAndGenerateNew") {
                setTimeout(() => {
                    userInput.value = "";
                    userInput.select();
                    const champName = document.getElementById("championName");
                    const fullImage = document.getElementById("fullImage");
                    const expirationDate = new Date(Date.now() + 24 * 60 * 60 * 1000); // 24 hours
                    champName.innerText = "";
                    fullImage.style.transition = "none"
                    fullImage.style.opacity = 0;
                    fullImage.src = "";
                    pixelationDifficulty = messageAsJson.pixelationDifficulty;
                    image = messageAsJson.image;
                    if (userInput.classList.contains("correct-border")) {
                        userInput.classList.remove("correct-border");
                    }
                    difficultySelector.value = pixelationDifficulty;
                    setCookie("pixelChamp", pixelationDifficulty, expirationDate.toUTCString());
                    const options = difficultySelector.getElementsByTagName("option");
                    for (const option of options) {
                        if(option.value === pixelationDifficulty){
                            option.setAttribute("hidden", "");
                        } else {
                            option.removeAttribute("hidden");
                        }
                    }
                    resetBonusBar();

                    // Save variables
                    fullImageSource = atob(messageAsJson.origImage);
                    championName = messageAsJson.championName;
                    addImageToCanvas(image);
                    isFormSubmissionBlocked = false; // Unblock form submission
                }, 4000);
            } else if (messageAsJson.status === "WarframeData") {

            }
        }
    }

    ws.onclose = (event) => { // Do this when the WS-Server stops
        clearTimeout(this.pingTimeout);
    }

</script>
<div class="w-full flex justify-center">
    <div class="absolute right-0 max-w-[256px] flex flex-col <?= __("invLink") ?>">
        <button 
            id="inviteLink"
            class="bg-tag-navy flex justify-center m-4 p-4 rounded cursor-pointer hover:opacity-80 active:opacity-70"
            onclick="copyInviteLink(this, '<?= __('Copied') ?>', 0, 'top-right', '-mt-8 animate-moveUpAndFadeOut');">

            <h1 class="font-bold text-center">&#128279; <?= __("Copy Invite Link") ?></h1>
        </button>
        <div id="userList" class="bg-dark mx-4 mb-4 p-4 rounded">
            <h1 class="font-bold"><?= __("Users inside this room:") ?></h1>
            <ol class="list-decimal list-inside"></ol>
        </div>
    </div>
    <div class="absolute left-0 max-w-[256px] flex flex-col">
        <div id="leaderboard" class="bg-dark m-4 p-4 rounded">
            <h1 class="font-bold text-xl pb-2 text-center"><?= __("Leaderboard:") ?></h1>
            <ol class="list-decimal list-inside" id="leaderboardList">
                <?php 
                $leaderboard = $db->getTopPlayers();
                if ($leaderboard !== false) {
                    foreach ($leaderboard as $index => $player) {
                        echo "<li class='pt-2 pb-1 border-b border-dashed border-[#21222c] flex items-center'>";
                        echo "<div class='w-40 truncate'>" . $index+1 . ". " .  $player["username"] . "</div>";
                        echo "<span class='ml-auto'>(" . $player["points"] . ")</span>";
                        echo "</li>";
                    }
                }
                ?>
            </ol>
        </div>
    </div>
    <div class="flex justify-center gap-x-8 mt-40 bg-dark rounded p-4 w-[82rem] h-[50rem]">
        <div id="canvasContainer" class="mb-4 flex flex-col w-3/5 h-full">
            <h1 class="text-3xl pl-2 pt-1 font-bold mb-4"><?= __("Pixel Guesser") ?></h1>
            <canvas id="pixelatedCanvas" width="640" height="640" class="w-[640px] h-[640px] mx-auto"></canvas>
            <img id="fullImage" src="" alt="Full Image" width="640" height="640" class="mt-[3.5rem] ml-[3.4rem]">
            <span class="text-xl h-4 mt-4 mx-auto animate-fadeIn opacity-0" id="championName"></span>
        </div>
        <div class="flex items-center flex-col justify-start w-2/5 gap-y-4">
            <span class="text-xl"><?= __("Bonus Points: ") ?></span>
            <div id="bonus-bar" class="bonus-bar"></div>
            <div id='chatContainer' class='bg-darker w-full max-h-[32rem] h-full p-2 flex flex-col-reverse overflow-auto twok:text-base fullhd:text-sm'></div>
            <?php 
            if(isset($_SESSION['user']['email'], $_SESSION['user']['username'])){ 
                echo '<div class="text-xl">'.__("Points: "); 
                $points = $db->getPoints($_SESSION['user']['username']); 
                if($points !== false) { 
                    echo "<span id='gamePoints' class='font-bold'>".$points."</span>"; 
                }
                echo '</div>';
            } else {
                echo "<div class='text-xl cursor-help' x-data=\"{ showNotice: false }\" x-cloak @mouseover='showNotice = true' @mouseout='showNotice = false'>".__('Points: ')."???
                        <div class='flex justify-center gap-x-0 -mt-8' x-cloak>
                            <span class='text-sm absolute backdrop-blur-2xl bg-black/80 p-2 rounded' x-show='showNotice' x-transition>".sprintf(__("Please %slogin%s or %sregister%s to see and save your score"), "<a href='/login' class='underline'>", "</a>", "<a href='/register' class='underline'>", "</a>")."</span>
                        </div></div>";
            }
            ?>
            <div class="flex flex-col justify-center items-center w-full">
                <form method="post" class="text-center flex my-4 w-full justify-center" id="championForm" onsubmit="checkChamp(); return false;" autocomplete="off">
                    <input  type="text" 
                            name="champion_input"
                            placeholder="Excalibur, Volt, etc."
                            class="w-1/2 autofill:text-black text-black border-2 border-solid border-white p-2 focus:border-2 focus:border-solid focus:border-white <?php if (isset($isCorrect)) echo $isCorrect ? 'correct-border' : 'incorrect-border'; ?>"
                            onkeydown="handleInputKeyDown(event)">
                    <div id="suggestions"></div>
                    <button type="submit" class="bg-tag-navy text-white px-4 py-2 border-2 border-solid border-tag-navy">&#10148;</button>
                </form>
                <div class="flex justify-center items-center mt-4">
                    <span class="text-xl"><?= __("Difficulty: ") ?></span>
                    <select id="difficulty-selector"  aria-label="Difficulty Selector"
                            class="text-center h-8 w-28 align-middle mr-2.5 ml-2.5 text-base bg-[#eee] text-black active:bg-[#ccc] focus:outline-none border-none"
                            onchange="setCookie('pixelChamp', this.value); this.disabled = true; location.reload();">
                        <option value="easy" <?= (!isset($_COOKIE["pixelChamp"]) || $_COOKIE["pixelChamp"] == "easy") ? "hidden" : ""; ?>><?= __("Easy") ?></option>
                        <option value="medium" <?= (isset($_COOKIE["pixelChamp"]) && $_COOKIE["pixelChamp"] == "medium") ? "hidden" : ""; ?>><?= __("Medium") ?></option>
                        <option value="hard" <?= (isset($_COOKIE["pixelChamp"]) && $_COOKIE["pixelChamp"] == "hard") ? "hidden" : ""; ?>><?= __("Hard") ?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

    <script>
    var warframeKeys;
    const suggestionsContainer = document.getElementById("suggestions");
    const userInput = document.querySelector("input[name='champion_input']");

    userInput.addEventListener("input", handleInput);
    userInput.addEventListener("focus", handleInput);

    document.addEventListener("click", function(event) {
        if (!userInput.contains(event.target) && !suggestionsContainer.contains(event.target)) {
            clearSuggestions();
        }
    });

    async function handleInput(event) {
        if (!warframeKeys) {
            console.log('Warframe keys are not available yet.');
            return;
        }

        const inputText = event.target.value.toLowerCase();

        if (inputText.length >= 1) {
            const matchingChampionsNames = Object.keys(warframeKeys).filter(championName =>
                championName.toLowerCase().includes(inputText)
            );

            displaySuggestions(matchingChampionsNames);
        } else {
            clearSuggestions();
        }
    }

    let highlightedIndex = -1;
    let arrowKeyPressed = false;
    let emptySuggestions = true;

    function displaySuggestions(championList) {
        suggestionsContainer.innerHTML = "";
        arrowKeyPressed = false;
        emptySuggestions = false;
        const maxSuggestions = 5;

        suggestionsContainer.addEventListener("mouseenter", () => {
            arrowKeyPressed = false;
        });

        const sortedChampionList = championList.sort((a, b) => {
            const aStartsWithInput = a.toLowerCase().startsWith(userInput.value.toLowerCase());
            const bStartsWithInput = b.toLowerCase().startsWith(userInput.value.toLowerCase());

            if (aStartsWithInput && !bStartsWithInput) {
                return -1;
            } else if (!aStartsWithInput && bStartsWithInput) {
                return 1;
            } else {
                return a.localeCompare(b);
            }
        });

        for (let i = 0; i < Math.min(championList.length, maxSuggestions); i++) {
            const suggestion = document.createElement("div");
            suggestion.textContent = championList[i];
            suggestion.classList.add("suggestion");

            suggestion.addEventListener("mouseenter", () => {
                removeHighlight();
            });

            suggestion.addEventListener("click", () => {
                userInput.value = championList[i];
                clearSuggestions();
            });

            suggestionsContainer.appendChild(suggestion);

            if(championList.length == 1){
                highlightSuggestion(0);
            }
        }
    }

    function highlightSuggestion(index) {
        removeHighlight();
        if (index >= 0 && index < suggestionsContainer.children.length) {
            highlightedIndex = index;
            suggestionsContainer.children[index].style.backgroundColor = "#ccc";
        }
    }

    function removeHighlight() {
        if (highlightedIndex !== -1) {
            suggestionsContainer.children[highlightedIndex].style.backgroundColor = "";
            highlightedIndex = -1;
        }
    }

    function handleInputKeyDown(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            if (highlightedIndex !== -1 && !emptySuggestions) {
                userInput.value = suggestionsContainer.children[highlightedIndex].textContent;
                clearSuggestions();
            } else {
                checkChamp();
            }
        } else if (event.key === "ArrowDown" || event.key === "Tab") {
            event.preventDefault();
            if (!arrowKeyPressed) {
                arrowKeyPressed = true;
                highlightSuggestion(0); // Highlight the first suggestion on the first arrow key press
            } else if (highlightedIndex < suggestionsContainer.children.length - 1) {
                highlightSuggestion(highlightedIndex + 1);
            }
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            if (!arrowKeyPressed) {
                arrowKeyPressed = true;
                highlightSuggestion(suggestionsContainer.children.length - 1); // Highlight the last suggestion on the first arrow key press
            } else if (highlightedIndex > 0) {
                highlightSuggestion(highlightedIndex - 1);
            }
        }
    }

    // Add this event listener to start highlighting on the first arrow key press
    document.addEventListener("keydown", (event) => {
        if (event.key === "ArrowDown" || event.key === "ArrowUp") {
            event.preventDefault();
            if (highlightedIndex === -1) {
                highlightSuggestion(0);
            }
        }
    });

    function clearSuggestions() {
        suggestionsContainer.innerHTML = "";
        arrowKeyPressed = false;
        emptySuggestions = true;
    }

    function addImageToCanvas(base64Image) {
        // Remove the old canvas if it exists
        const oldCanvas = document.getElementById("pixelatedCanvas");
        if (oldCanvas) {
            oldCanvas.parentNode.removeChild(oldCanvas);
        }

        // Create a new canvas element
        const canvas = document.createElement("canvas");
        canvas.id = "pixelatedCanvas";
        canvas.width = 640; // Set the desired width
        canvas.height = 640; // Set the desired height
        canvas.classList.add("w-[640px]", "h-[640px]", "mx-auto");
        document.getElementById("canvasContainer").insertBefore(canvas, document.getElementById("canvasContainer").children[1]);

        const context = canvas.getContext("2d", { willReadFrequently: true });
        const image = new Image();

        // Set the image source to the base64 string
        image.src = base64Image;

        // Wait for the image to load before drawing it to the canvas
        image.onload = function() {
            context.drawImage(image, 0, 0, canvas.width, canvas.height);
        };

        image.onerror = function() {
            console.error("Error loading the image.");
        };
    }

    // Function to calculate the average color of the given pixel data
    function getAverageColor(data) {
        let r = 0, g = 0, b = 0, count = 0;

        for (let i = 0; i < data.length; i += 4) {
            r += data[i];     // Red
            g += data[i + 1]; // Green
            b += data[i + 2]; // Blue
            count++;
        }

        return {
            r: Math.floor(r / count),
            g: Math.floor(g / count),
            b: Math.floor(b / count)
        };
    }

    function updateInputBorder(isCorrect) {
        const userInput = document.querySelector("input[name='champion_input']");

        if (isCorrect) {
            userInput.classList.remove("incorrect-border", "animate-border-color");
            userInput.classList.add("correct-border");
        } else {
            userInput.classList.remove("correct-border");
            userInput.classList.add("incorrect-border");
            setTimeout(() => {
                userInput.classList.add("animate-border-color");
                userInput.classList.remove("incorrect-border");
                setTimeout(() => {
                    userInput.classList.remove("animate-border-color");
                }, 500);
            }, 500); // Remove incorrect-border class immediately without fade out
        }
    }

    function calculateBonusPoints() {
        const bonusBar = document.getElementById('bonus-bar');
        const scaleXValue = window.getComputedStyle(bonusBar).transform.split(',')[0].split('(')[1];
        const scaleXPercentage = parseFloat(scaleXValue) * 100;

        // Calculate bonus points proportional to the scaleX percentage
        const bonusPoints = Math.floor(scaleXPercentage);

        return Math.min(bonusPoints, 100); // Cap bonusPoints at 100
    }

    function resetBonusBar() {
        const bonusBar = document.getElementById('bonus-bar');

        // Add the stopped class to stop the animation
        bonusBar.classList.add('stopped');

        // Remove the animation class to reset the animation
        bonusBar.classList.remove('decreaseBonus');

        // Force a reflow to ensure the animation class is removed before reapplying it
        void bonusBar.offsetWidth;

        // Remove the stopped class to restart the animation from full width
        bonusBar.classList.remove('stopped');

        // Reapply the animation class to restart the animation
        bonusBar.classList.add('decreaseBonus');
    }

    function checkChamp() {
        if (isFormSubmissionBlocked) {
            return; // Don't process the form if submission is blocked
        }
        const userInput = document.querySelector("input[name='champion_input']");
        const correctAnswer = championName;
        const userAnswer = userInput.value.toLowerCase().replace(/'/g, '');

        const isCorrect = userAnswer === atob(correctAnswer).toLowerCase().replace(/'/g, '');
        updateInputBorder(isCorrect);

        if (isCorrect) {
            // Send a message to the WebSocket server indicating the correct answer
            isFormSubmissionBlocked = true;
            let correctAnswerMessage =  {
                roomid: getCookie("roomCode"),
                name: name,
                difficulty: getCookie("pixelChamp"),
                request: "correctAnswer",
                answer: atob(correctAnswer),
                bonuspoints: calculateBonusPoints()
            };
            ws.send(JSON.stringify(correctAnswerMessage))

            const startTime = performance.now();
            const champName = document.getElementById("championName");
            const fullImage = document.getElementById("fullImage");
            champName.innerText = "<?= __("It was: ") ?>"+atob(championName);
            fullImage.src = fullImageSource.replace("/hdd1/warframe/", '');

            function revealFullImage(timestamp) {
                const elapsedTime = timestamp - startTime;
                const progress = Math.min(elapsedTime / 1000, 1);
                document.getElementById("fullImage").style.opacity = progress;

                if (progress < 1) {
                    requestAnimationFrame(revealFullImage);
                } else {
                    champName.style.opacity = 1;
                }
            }

            requestAnimationFrame(revealFullImage);
        }
    }

        // Select element for difficulty
        const difficultySelector = document.getElementById('difficulty-selector');

        // Event listener for select menu change
        difficultySelector.addEventListener('change', function () {
            const newDifficulty = this.value;

            // Construct the message to send
            const message = {
                request: 'changeDifficulty',
                roomid: getCookie('roomCode'),
                difficulty: newDifficulty
            };

            // Send the message to the websocket server
            ws.send(JSON.stringify(message));
        });
    </script>
<?php
?>