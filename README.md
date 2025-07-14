# FrameGuesser
FrameGuesser is a real-time multiplayer web game where players guess the correct Warframe based on a pixelated image. The game leverages WebSockets for smooth client-server communication and dynamic room-based gameplay.

## How It Works
A random Warframe is selected from the official item database.

- Its image is pixelated based on a chosen difficulty level (easy, medium, or hard).
- The image is sent to all connected players in a game room via WebSocket.
- Players race to guess the correct Warframe name.
- On a correct guess, the game instantly generates and distributes a new challenge.

## Features
- Real-time multiplayer rooms via WebSocket
- Dynamic pixelation using sharp for image transformation
- Room-based difficulty settings
- Unique name and color assignment for each player
- Fully server-driven gameplay with secure HTTPS setup
- Live game state updates and player list synchronization
- Logging of player actions and server events for debugging and moderation

Tech Stack
- Node.js with native https and ws modules
- WebSocket Server for real-time bidirectional communication
- warframe-items for up-to-date Warframe data
- sharp for image pixelation and format conversion
- File-based logging for server activity