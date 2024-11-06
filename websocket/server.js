import { createServer } from 'https';
import { readFileSync } from 'fs';
import { WebSocketServer } from 'ws';
import fs from 'fs';
import path from 'path';
import ansiRegex from 'ansi-regex';
import util from 'util';
import Items from 'warframe-items';
import sharp from 'sharp';
// import '/hdd1/warframe/websocket/consoleHandler.js';

const logStream = fs.createWriteStream('/hdd1/warframe/data/logs/server.log', { flags: 'a' });
const logPath = '/hdd1/warframe/data/logs/server.log';
const attackLogPath = '/hdd1/warframe/data/logs/attacks.log';
const maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
const roomPlayers = {}; // Initializes an object to store connected players for each room
const roomSettings = {};
const correctAnswerTimers  = {};

const items = new Items({ category: ['Warframes'] });

const warframes = items.filter(warframe => 
  warframe.name !== 'Bonewidow' && warframe.name !== 'Voidrig'
);

// Attach the uncaught exception handler
process.on('uncaughtException', handleCrash);
process.on('unhandledRejection', handleShutdown);

const originalConsoleLog = console.log;

// Redirect console output to the log file
function logWS(type, message, ...args) {
  trimLogFileIfNeeded();

  const timestamp = new Date().toLocaleTimeString();
  const wsType = type === 'server' ? '\x1b[35mWS-Server\x1b[0m' : '\x1b[36mWS-Client\x1b[0m';
  const formattedMessage = `\x1b[2m[%s]\x1b[0m [${wsType}]: ${message}`;

  const logMessage = util.format(formattedMessage, timestamp, ...args);
  logStream.write(`${logMessage.replace(ansiRegex(), '')}\n`);
  
  originalConsoleLog(logMessage);
}

// Start and create Websocket Server
const server = createServer({
  cert: readFileSync('/etc/letsencrypt/live/dasnerdwork.net/fullchain.pem'),
  key: readFileSync('/etc/letsencrypt/live/dasnerdwork.net/privkey.pem')
}).listen(8082);
const wss = new WebSocketServer({ server });
const currentPatch = fs.readFileSync('/hdd1/clashapp/data/patch/version.txt', 'utf-8');
// const validChamps = JSON.parse(fs.readFileSync('/hdd1/clashapp/data/patch/'+currentPatch+'/data/de_DE/champion.json', 'utf-8'))["data"];
var lastClient = "";

logWS('server', "Successfully started the Websocket-Server!");

server.on('error', (err) => {
  logWS('server', 'Server error:', err);
});

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////// STANDARD OPERATION FUNCTIONS ////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function sendWarframeData(ws) {
  const warframeData = warframes.map(item => ({
    name: item.name,
    identifier: item.uniqueName // You can change this to any other property that identifies the warframe
  }));
  
  ws.send(JSON.stringify({ status: 'WarframeData', data: warframeData }));
}

async function pixelateImage(filePath, blockSize) {
    // Load the image
    const image = sharp(filePath);

    // Get image metadata to know its dimensions
    const { width, height } = await image.metadata();

    // Resize the image to the block size to create the pixelated effect
    const pixelatedBuffer = await image
        .resize({
            width: Math.ceil(width / blockSize), // Downscale by block size
            height: Math.ceil(height / blockSize), 
            kernel: sharp.kernel.nearest // Nearest neighbor interpolation for pixelation
        })
        .toBuffer();

    // Upscale back to original size to create the pixelated effect
    const finalImageBuffer = await sharp(pixelatedBuffer)
        .resize(width, height, { kernel: sharp.kernel.nearest }) // Resize back to original size
        .toFormat('webp') // Output as WebP
        .toBuffer();

    // Convert the image to base64 and return
    return `data:image/webp;base64,${finalImageBuffer.toString('base64')}`;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////// WEBSOCKET SERVER OPERATIONS /////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

wss.on('connection', function connection(ws, req) {
  let d = new Date();
  logWS('client', 'Client websocket connection initiated from \x1b[4m%s\x1b[0m:%d, Total (%d) on %s', req.headers['x-forwarded-for'].split(/\s*,\s*/)[0], ws._socket.remotePort, wss.clients.size, d.getDate()+"/"+(d.getMonth()+1)+"/"+d.getFullYear() % 100);

  ws.on('message', function message(data) {
    let newClient = req.headers['x-forwarded-for'].split(/\s*,\s*/)[0] + ':' + req.socket.remotePort;
    let dataAsString = data.toString();
    if(Array.from(dataAsString)[0] == "{"){        // If data is an [Object object]
      var dataAsJSON = JSON.parse(dataAsString);
      let requestMessage = "";
      if(dataAsJSON.request != "minigames"){
        switch (dataAsJSON.request) {
          case "firstConnect":
            requestMessage = "\x1b[36mfirstConnect\x1b[0m";
            break;
          case "add":
            requestMessage = "\x1b[32madd\x1b[0m";
            break;
          case "remove":
            requestMessage = "\x1b[31mremove\x1b[0m";
            break;
          case "rate":
            requestMessage = "\x1b[33mrate\x1b[0m";
            break;
          case "swap":
            requestMessage = "\x1b[35mswap\x1b[0m";
            break;
        }
        if (typeof dataAsJSON.champname === 'undefined') {
          var nameForMessage = dataAsJSON.name;
        } else {
          var nameForMessage = dataAsJSON.champname;
        }
        let message = '{"teamid":"'+dataAsJSON.teamid+'","name":"'+nameForMessage+'","request":"'+requestMessage+'"}';
        if(newClient == lastClient){ // If the same client is still sending data no "Received following data from" text is necessary
          logWS('client', message);
        } else {
          logWS('server', 'Received following data from %s', newClient);
          logWS('client', '%s', message);
          lastClient = newClient;
        }
      }

      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      ////////////////////////////////////////////////// ADD TO FILE ///////////////////////////////////////////////////////
      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

      if(dataAsJSON.request == "minigames"){
          sendWarframeData(ws);
          ws.location = dataAsJSON.roomid;
          // When a new player joins a room
          if (!roomPlayers[dataAsJSON.roomid]) {
            roomPlayers[dataAsJSON.roomid] = [];
          }
          // Initialize roomSettings if it doesn't exist for the current room
          if (!roomSettings[dataAsJSON.roomid]) {
            roomSettings[dataAsJSON.roomid] = {};
          }
          // Save room difficulty
          if(!roomSettings[dataAsJSON.roomid]["Difficulty"]){
            roomSettings[dataAsJSON.roomid]["Difficulty"] = dataAsJSON.difficulty;
          }

          if(dataAsJSON.action == "generate"){
            async function getWeaponData() {
              return new Promise(async (resolve, reject) => {
                  try {
                      // Pick a random warframe
                      const randomIndex = Math.floor(Math.random() * warframes.length);
                      const item = warframes[randomIndex];
                      const imagePath = `/hdd1/warframe/data/warframes/${item.imageName.replace('.png', '.webp')}`;
          
                      // Determine the pixelation difficulty
                      const pixelationDifficulty = roomSettings[dataAsJSON.roomid]["Difficulty"] || dataAsJSON.difficulty;
                      let blockSize = 25; // Default block size
          
                      // Set the block size based on difficulty
                      switch (pixelationDifficulty) {
                          case "easy":
                              blockSize = 25;
                              break;
                          case "medium":
                              blockSize = 33;
                              break;
                          case "hard":
                              blockSize = 50;
                              break;
                          default:
                              blockSize = 25; // Fallback to easy if difficulty is unknown
                              break;
                      }
          
                      // Load and pixelate the image
                      const pixelatedImage = await pixelateImage(imagePath, blockSize);
          
                      // Return the pixelated data for the selected weapon
                      resolve({
                          name: item.name,
                          origPath: imagePath,
                          path: pixelatedImage,
                          key: item.uniqueName
                      });
                  } catch (err) {
                      console.error('Error processing image:', err);
                      reject({
                          name: 'Unknown',
                          origPath: 'No image available',
                          path: 'No image available',
                          key: 'unknown'
                      });
                  }
              });
            }
          
            async function getRandomWeapon() {
                const weaponData = await getWeaponData();
                
                if (!weaponData || !weaponData.name) {
                    logWS('server', 'Error loading weapon data');
                    return null;
                }
            
                // No need to get a random index, as we're only fetching one weapon
                return weaponData;
            }
          
            async function generateRandomChampion() {
              const randomWeapon  = await getRandomWeapon();
              if (!randomWeapon ) {
                  logWS('server', 'Error getting random weapon data');
                  return;
              }

              const weaponName = randomWeapon.name;
              const image = randomWeapon.path;
              const origImage = randomWeapon.origPath;
          
              // Sending pixelation settings and image path to the new player
              const pixelationSettings = {
                status: 'PixelateAndGenerate',
                pixelationDifficulty: roomSettings[dataAsJSON.roomid]["Difficulty"],
                image: image,
                origImage: Buffer.from(origImage).toString('base64'),
                championName: Buffer.from(weaponName).toString('base64')
              };
              wss.clients.forEach(function each(client) {
                if (client.location == dataAsJSON.roomid) {
                  client.send(JSON.stringify(pixelationSettings));
                }
              });
            }
            generateRandomChampion();
          }
        var possibleColors = ["red-700","green-800","blue-800","pink-700","lime-500","cyan-600","amber-600","yellow-400","purple-700","rose-400"];
        wss.clients.forEach(function each(client) {              
          if(possibleColors.includes(client.color)){ // This removes every "already used" color from the array above
            var colorIndex = possibleColors.indexOf(client.color);
            if (colorIndex > -1) { // only splice array when item is found
              possibleColors.splice(colorIndex, 1); // 2nd parameter means remove one item only
            }
          }
        });
        if(possibleColors.length >= 1){
          ws.color = possibleColors[Math.floor(Math.random()*possibleColors.length)];
        } else {
          const colorList = ["red-700","green-800","blue-800","pink-700","lime-500","cyan-600","amber-600","yellow-400","purple-700","rose-400"];
          ws.color = colorList[Math.floor(Math.random()*colorList.length)];
        }
        if(dataAsJSON.name == ""){
          var possibleNames = ["Lotus","Ordis","Teshin","Ballas","Hunhow","Konzu","Loid","Kahl-175","Clem","Vor"];
          wss.clients.forEach(function each(client) {
            if(client.location == dataAsJSON.roomid){
              if(possibleNames.includes(client.name)){ // This removes every "already used" name from the array above
                var index = possibleNames.indexOf(client.name);
                if (index > -1) { // only splice array when item is found
                  possibleNames.splice(index, 1); // 2nd parameter means remove one item only
                }
              }
            }
          });
          if(possibleNames.length >= 1){
            ws.name = possibleNames[Math.floor(Math.random()*possibleNames.length)];
          } else {
            const nameList = ["Krug","Gromp","Sentinel","Brambleback","Raptor","Scuttler","Wolf","Herald","Nashor","Minion"];
            ws.name = nameList[Math.floor(Math.random()*nameList.length)];
          }
        } else {
          ws.name = dataAsJSON.name;
        }
        ws.send('{"status":"RoomJoined","name":"'+ws.name+'","location":"'+ws.location+'","message":"(You) joined the room.","color":"'+ws.color+'","difficulty":"'+roomSettings[dataAsJSON.roomid]["Difficulty"]+'"}');
        wss.clients.forEach(function each(client) {
          if(client.location == dataAsJSON.roomid && client != ws){
            client.send('{"status":"Message","message":"joined the room.","name":"'+ws.name+'","color":"'+ws.color+'"}');
          }
        });

        // Add the new player to the connected players array for the specific room
        roomPlayers[dataAsJSON.roomid].push(ws.name);

        // Send the updated player list for the specific room to all players in that room
        const playerListUpdate = {
          status: 'PlayerListUpdate',
          players: roomPlayers[dataAsJSON.roomid],
          colors: {} // Use an object for mapping names to colors
        };

        // Collect the colors of all players in the room using a mapping
        wss.clients.forEach(function each(client) {
          if (client.location == dataAsJSON.roomid) {
            playerListUpdate.colors[client.name] = client.color;
          }
        });

        wss.clients.forEach(function each(client) {
          if (client.location == dataAsJSON.roomid) {
            client.send(JSON.stringify(playerListUpdate));
          }
        });

      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////// CORRECT ANSWER  ////////////////////////////////////////////////////
      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

      } else if(dataAsJSON.request == "correctAnswer"){ 
        let answerer = dataAsJSON.name && dataAsJSON.name.trim() ? dataAsJSON.name : "Someone";
        logWS('server', answerer +  " gave the correct answer in " + dataAsJSON.roomid + " with: " + dataAsJSON.answer + " (+" + (100 + parseInt(dataAsJSON.bonuspoints)) + ")");
        async function getWeaponData() {
          return new Promise(async (resolve, reject) => {
              try {
                  // Pick a random warframe
                  const randomIndex = Math.floor(Math.random() * warframes.length);
                  const item = warframes[randomIndex];
                  const imagePath = `/hdd1/warframe/data/warframes/${item.imageName.replace('.png', '.webp')}`;
      
                  // Determine the pixelation difficulty
                  const pixelationDifficulty = roomSettings[dataAsJSON.roomid]["Difficulty"] || dataAsJSON.difficulty;
                  let blockSize = 25; // Default block size
      
                  // Set the block size based on difficulty
                  switch (pixelationDifficulty) {
                      case "easy":
                          blockSize = 25;
                          break;
                      case "medium":
                          blockSize = 33;
                          break;
                      case "hard":
                          blockSize = 50;
                          break;
                      default:
                          blockSize = 25; // Fallback to easy if difficulty is unknown
                          break;
                  }
      
                  // Load and pixelate the image
                  const pixelatedImage = await pixelateImage(imagePath, blockSize);
      
                  // Return the pixelated data for the selected weapon
                  resolve({
                      name: item.name,
                      origPath: imagePath,
                      path: pixelatedImage,
                      key: item.uniqueName
                  });
              } catch (err) {
                  console.error('Error processing image:', err);
                  reject({
                      name: 'Unknown',
                      origPath: 'No image available',
                      path: 'No image available',
                      key: 'unknown'
                  });
              }
          });
        }
          
        async function getRandomWeapon() {
            const weaponData = await getWeaponData();
            
            if (!weaponData || !weaponData.name) {
                logWS('server', 'Error loading weapon data');
                return null;
            }
        
            // No need to get a random index, as we're only fetching one weapon
            return weaponData;
        }
  
      if (!correctAnswerTimers[dataAsJSON.roomid]) {
          // Initialize a timestamp for this room
          correctAnswerTimers[dataAsJSON.roomid] = 0;
      }
  
      const currentTime = Date.now();
  
      if (currentTime - correctAnswerTimers[dataAsJSON.roomid] >= 4000) {
          // Record the current timestamp
          correctAnswerTimers[dataAsJSON.roomid] = currentTime;
  
          wss.clients.forEach(function each(client) {
              if (client.location == dataAsJSON.roomid) {
                  client.send('{"status":"Message","message":"guessed the correct answer: %1","answer":"' + dataAsJSON.answer + '","name":"' + ws.name + '","color":"'+ws.color+'","bonuspoints":"'+dataAsJSON.bonuspoints+'"}');
              }
          });
  
          async function generateNewRandomWeaponAndNotify() {
            const randomWeapon  = await getRandomWeapon();
            if (!randomWeapon ) {
                logWS('server', 'Error getting random weapon data');
                return;
            }

            const weaponName = randomWeapon.name;
            const image = randomWeapon.path;
            const origImage = randomWeapon.origPath;
        
            // Sending pixelation settings and image path to the new player
            const pixelationSettings = {
              status: 'PixelateAndGenerateNew',
              pixelationDifficulty: roomSettings[dataAsJSON.roomid]["Difficulty"],
              image: image,
              origImage: Buffer.from(origImage).toString('base64'),
              championName: Buffer.from(weaponName).toString('base64')
          };
            wss.clients.forEach(function each(client) {
              if (client.location == dataAsJSON.roomid) {
                client.send(JSON.stringify(pixelationSettings));
              }
            });
        }
        generateNewRandomWeaponAndNotify();
      }

     //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
     ///////////////////////////////////////////////// CHANGE DIFFICULTY///////////////////////////////////////////////////
     //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

      } else if(dataAsJSON.request == "changeDifficulty"){
        const validDifficulties = ['easy', 'medium', 'hard'];
        if (validDifficulties.includes(dataAsJSON.difficulty)) {
          if (roomSettings.hasOwnProperty(dataAsJSON.roomid)) {
            if (!roomSettings[dataAsJSON.roomid].hasOwnProperty('Difficulty')) {
                console.error(`Room ${dataAsJSON.roomid} does not have the 'Difficulty' attribute`);
            } else {
                roomSettings[dataAsJSON.roomid]['Difficulty'] = dataAsJSON.difficulty;
            }
          } else {
              console.error(`Room ${dataAsJSON.roomid} does not exist`);
          }
        }
      }
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////// ON TEXT MESSAGE ///////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    } else {
      if(newClient == lastClient){ // If the same client is still sending data no "Received following data from" text is necessary
        logWS('client', 'Data: %s', data.toString());
      } else {
        logWS('server', 'Received following data from %s:%d', req.headers['x-forwarded-for'].split(/\s*,\s*/)[0], ws._socket.remotePort, data.toString());
        logWS('client', '%s', data.toString());
        lastClient = newClient;
      }
    }
  });

  ws.send('Handshake successful: Server received client request and answered.');

  ws.on('close', function close() {
    logWS('server', 'Connection of client closed from %s:%d, Total (%d) on %s', req.headers['x-forwarded-for'].split(/\s*,\s*/)[0], ws._socket.remotePort, wss.clients.size, d.getDate()+"/"+(d.getMonth()+1)+"/"+d.getFullYear() % 100);

    // Find the room (location) of the closing client
    const closedRoom = ws.location;

    // Remove the closed client's name from the roomPlayers object
    if (roomPlayers[closedRoom]) {
        roomPlayers[closedRoom] = roomPlayers[closedRoom].filter(player => player !== ws.name);
    }

    // Collect the colors of the remaining players in the room using a mapping
    const remainingPlayerColors = {};
    wss.clients.forEach(function each(client) {
      if (client.location === closedRoom && client !== ws) {
        remainingPlayerColors[client.name] = client.color;
      }
    });

    // Send the updated player list for the specific room to all players in that room
    const playerListUpdate = {
      status: 'PlayerListUpdate',
      players: roomPlayers[closedRoom],
      colors: remainingPlayerColors
    };

    wss.clients.forEach(function each(client) {
      if(client.location == ws.location && client != ws){
        if (roomPlayers[closedRoom]) {
          client.send('{"status":"Message","message":"left the room.","name":"'+ws.name+'","color":"'+ws.color+'"}');
          client.send(JSON.stringify(playerListUpdate));        
        } else {
          client.send('{"status":"Message","message":"left the session.","name":"'+ws.name+'","color":"'+ws.color+'"}');
        }
      }
    });
  });
});

function trimLogFileIfNeeded() {
  const fileSize = fs.statSync(logPath).size;
  if (fileSize > maxFileSize) {
    const fileData = fs.readFileSync(logPath, 'utf8').split('\n');
    const trimmedData = fileData.slice(Math.ceil(fileData.length / 2)).join('\n');
    fs.writeFileSync(logPath, trimmedData, 'utf8');
    logWS('server', "Maximum logsize exceeded, removed first half of it.");
  }
}

function handleCrash(error) {
  var currentTime = new Date().toLocaleTimeString();
  const crashMessage = `[${currentTime}] [Server Crash]: ${error.stack}\n`;
  fs.appendFileSync(logPath, crashMessage, 'utf8');
  process.exit(1);
}

function handleShutdown(error){
  var currentTime = new Date().toLocaleTimeString();
  const crashMessage = `[${currentTime}] [Server Shutdown]: ${error.stack}\n`;
  fs.appendFileSync(logPath, crashMessage, 'utf8');
}