const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const path = require('path');

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
    cors: { origin: '*' }
});

// Serve static files
app.use(express.static(path.join(__dirname, 'public')));

// ==================== WORD LISTS ====================

const JUSTONE_WORDS = [
    'Chocolate', 'Praia', 'Futebol', 'Hospital', 'Pizza', 'Guitarra', 'Elefante',
    'Castelo', 'Astronauta', 'Diamante', 'Vulcão', 'Pirata', 'Carnaval', 'Deserto',
    'Submarino', 'Dragão', 'Safari', 'Labirinto', 'Tornado', 'Samurai', 'Faraó',
    'Gladiador', 'Viking', 'Ninja', 'Sereia', 'Unicórnio', 'Fantasma', 'Vampiro',
    'Montanha', 'Oceano', 'Foguetão', 'Tesouro', 'Coroa', 'Espada', 'Magia',
    'Relâmpago', 'Arco-íris', 'Dinossauro', 'Robot', 'Princesa', 'Cavaleiro',
    'Floresta', 'Cascata', 'Vulcão', 'Ilha', 'Farol', 'Bússola', 'Mapa',
    'Âncora', 'Navio', 'Cometa', 'Eclipse', 'Galáxia', 'Planeta', 'Estrela',
    'Música', 'Cinema', 'Teatro', 'Circo', 'Palhaço', 'Mágico', 'Acrobata',
    'Surfista', 'Mergulhador', 'Alpinista', 'Parapente', 'Skate', 'Bicicleta',
    'Gelado', 'Bolo', 'Café', 'Cerveja', 'Sushi', 'Hambúrguer', 'Tacos',
    'Panqueca', 'Croissant', 'Batata', 'Tomate', 'Ananás', 'Morango', 'Banana',
    'Leão', 'Tigre', 'Águia', 'Golfinho', 'Tubarão', 'Polvo', 'Borboleta',
    'Papagaio', 'Coruja', 'Pinguim', 'Canguru', 'Koala', 'Panda', 'Girafa',
    'Biblioteca', 'Universidade', 'Laboratório', 'Telescópio', 'Microscópio',
    'Fotografia', 'Pintura', 'Escultura', 'Museu', 'Concerto', 'Festival',
    'Camping', 'Piquenique', 'Churrasco', 'Karaoke', 'Discoteca', 'Casino',
    'Tatuagem', 'Perfume', 'Óculos', 'Chapéu', 'Gravata', 'Pijama',
    'Pastel de Nata', 'Bacalhau', 'Fado', 'Azulejo', 'Galo de Barcelos',
    'Cristiano Ronaldo', 'Benfica', 'Porto', 'Lisboa', 'Algarve', 'Madeira',
    'Sardinhas', 'Francesinha', 'Bifana', 'Vinho do Porto', 'Super Bock'
];

const IMPOSTOR_CATEGORIES = [
    { categoria: 'Animais', palavras: ['Gato', 'Cão', 'Cavalo', 'Leão', 'Elefante', 'Golfinho', 'Águia', 'Cobra', 'Urso', 'Lobo'] },
    { categoria: 'Comida', palavras: ['Pizza', 'Sushi', 'Hambúrguer', 'Massa', 'Gelado', 'Chocolate', 'Bacalhau', 'Francesinha', 'Bolo', 'Batata Frita'] },
    { categoria: 'Desporto', palavras: ['Futebol', 'Basquetebol', 'Ténis', 'Natação', 'Surf', 'Boxe', 'Ciclismo', 'Atletismo', 'Ski', 'Golfe'] },
    { categoria: 'Profissões', palavras: ['Médico', 'Professor', 'Bombeiro', 'Polícia', 'Cozinheiro', 'Piloto', 'Astronauta', 'Veterinário', 'Arquiteto', 'Cantor'] },
    { categoria: 'Países', palavras: ['Portugal', 'Brasil', 'Espanha', 'França', 'Itália', 'Japão', 'Austrália', 'Egito', 'México', 'Grécia'] },
    { categoria: 'Filmes', palavras: ['Titanic', 'Matrix', 'Star Wars', 'Harry Potter', 'Senhor dos Anéis', 'Jurassic Park', 'Avatar', 'Shrek', 'Batman', 'Frozen'] },
    { categoria: 'Instrumentos', palavras: ['Guitarra', 'Piano', 'Bateria', 'Violino', 'Flauta', 'Saxofone', 'Trompete', 'Harpa', 'Ukulele', 'Acordeão'] },
    { categoria: 'Lugares', palavras: ['Praia', 'Montanha', 'Floresta', 'Deserto', 'Cidade', 'Aldeia', 'Ilha', 'Rio', 'Lago', 'Caverna'] },
    { categoria: 'Transportes', palavras: ['Avião', 'Comboio', 'Barco', 'Mota', 'Bicicleta', 'Helicóptero', 'Submarino', 'Foguetão', 'Autocarro', 'Táxi'] },
    { categoria: 'Super-heróis', palavras: ['Spider-Man', 'Batman', 'Superman', 'Iron Man', 'Hulk', 'Thor', 'Wonder Woman', 'Deadpool', 'Wolverine', 'Flash'] },
    { categoria: 'Fruta', palavras: ['Maçã', 'Banana', 'Laranja', 'Morango', 'Ananás', 'Manga', 'Uva', 'Melancia', 'Pêssego', 'Kiwi'] },
    { categoria: 'Roupa', palavras: ['Calças', 'T-shirt', 'Vestido', 'Casaco', 'Sapatos', 'Chapéu', 'Cachecol', 'Luvas', 'Meias', 'Gravata'] },
    { categoria: 'Emoções', palavras: ['Alegria', 'Tristeza', 'Raiva', 'Medo', 'Surpresa', 'Amor', 'Nojo', 'Vergonha', 'Orgulho', 'Ciúme'] },
    { categoria: 'Estações do Ano', palavras: ['Primavera', 'Verão', 'Outono', 'Inverno'] },
    { categoria: 'Objetos de Casa', palavras: ['Televisão', 'Sofá', 'Frigorífico', 'Cama', 'Mesa', 'Cadeira', 'Espelho', 'Relógio', 'Candeeiro', 'Almofada'] }
];

// ==================== GAME STATE ====================

const rooms = new Map();

function generateRoomCode() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let code = '';
    for (let i = 0; i < 4; i++) code += chars[Math.floor(Math.random() * chars.length)];
    return rooms.has(code) ? generateRoomCode() : code;
}

function pickRandom(arr) {
    return arr[Math.floor(Math.random() * arr.length)];
}

// ==================== JUST ONE LOGIC ====================

function justoneNewRound(room) {
    room.round++;
    room.phase = 'show_word';
    room.currentWord = pickRandom(JUSTONE_WORDS);
    room.guesserIndex = (room.guesserIndex + 1) % room.players.length;
    room.clues = {};
    room.removedClues = [];
    room.guess = null;
    room.guessCorrect = null;
    room.timerEnd = null;
}

function justoneCheckDuplicates(room) {
    const clueMap = {};
    for (const [pid, clue] of Object.entries(room.clues)) {
        const normalized = clue.toLowerCase().trim().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        if (!clueMap[normalized]) clueMap[normalized] = [];
        clueMap[normalized].push(pid);
    }
    room.removedClues = [];
    for (const [norm, pids] of Object.entries(clueMap)) {
        if (pids.length > 1) {
            pids.forEach(pid => room.removedClues.push(pid));
        }
    }
}

// ==================== IMPOSTOR LOGIC ====================

function impostorNewRound(room) {
    room.round++;
    room.phase = 'show_role';
    const cat = pickRandom(IMPOSTOR_CATEGORIES);
    room.category = cat.categoria;
    room.currentWord = pickRandom(cat.palavras);
    room.impostorIndex = Math.floor(Math.random() * room.players.length);
    room.cluesGiven = [];
    room.currentTurn = 0;
    room.votes = {};
    room.guess = null;
    room.guessCorrect = null;
    room.timerEnd = null;
}

// ==================== SOCKET.IO ====================

io.on('connection', (socket) => {
    let currentRoom = null;
    let currentGame = null;

    // ---------- CREATE ROOM ----------
    socket.on('create_room', ({ game, playerName }) => {
        const code = generateRoomCode();
        const player = { id: socket.id, name: playerName, score: 0, connected: true };

        const room = {
            code,
            game,
            host: socket.id,
            players: [player],
            phase: 'lobby',
            round: 0,
            maxRounds: 5,
            // Just One specific
            guesserIndex: -1,
            currentWord: null,
            clues: {},
            removedClues: [],
            guess: null,
            guessCorrect: null,
            // Impostor specific
            category: null,
            impostorIndex: -1,
            cluesGiven: [],
            currentTurn: 0,
            votes: {},
            timerEnd: null,
        };

        rooms.set(code, room);
        socket.join(code);
        currentRoom = code;
        currentGame = game;

        socket.emit('room_created', { code, player });
        io.to(code).emit('state_update', getSafeState(room, socket.id));
    });

    // ---------- JOIN ROOM ----------
    socket.on('join_room', ({ code, playerName }) => {
        code = code.toUpperCase();
        const room = rooms.get(code);
        if (!room) return socket.emit('error_msg', 'Sala não encontrada!');
        if (room.phase !== 'lobby') return socket.emit('error_msg', 'O jogo já começou!');
        if (room.players.length >= 10) return socket.emit('error_msg', 'Sala cheia!');
        if (room.players.find(p => p.name === playerName)) return socket.emit('error_msg', 'Já existe alguém com esse nome!');

        const player = { id: socket.id, name: playerName, score: 0, connected: true };
        room.players.push(player);
        socket.join(code);
        currentRoom = code;
        currentGame = room.game;

        socket.emit('room_joined', { code, player, game: room.game });
        broadcastState(room);
    });

    // ---------- START GAME ----------
    socket.on('start_game', () => {
        const room = rooms.get(currentRoom);
        if (!room || room.host !== socket.id) return;

        if (room.game === 'justone') {
            if (room.players.length < 3) return socket.emit('error_msg', 'Precisas de pelo menos 3 jogadores!');
            justoneNewRound(room);
        } else {
            if (room.players.length < 3) return socket.emit('error_msg', 'Precisas de pelo menos 3 jogadores!');
            impostorNewRound(room);
        }
        broadcastState(room);
    });

    // ---------- JUST ONE: Ready (seen word) ----------
    socket.on('justone_ready', () => {
        const room = rooms.get(currentRoom);
        if (!room || room.game !== 'justone') return;
        if (room.phase === 'show_word') {
            room.phase = 'writing';
            room.timerEnd = Date.now() + 60000;
            broadcastState(room);
        }
    });

    // ---------- JUST ONE: Submit clue ----------
    socket.on('justone_clue', ({ clue }) => {
        const room = rooms.get(currentRoom);
        if (!room || room.game !== 'justone' || room.phase !== 'writing') return;
        const guesser = room.players[room.guesserIndex];
        if (socket.id === guesser.id) return;

        clue = clue.trim();
        if (clue.includes(' ')) return socket.emit('error_msg', 'Apenas uma palavra!');
        room.clues[socket.id] = clue;

        // Check if all non-guessers have submitted
        const nonGuessers = room.players.filter(p => p.id !== guesser.id);
        if (Object.keys(room.clues).length >= nonGuessers.length) {
            justoneCheckDuplicates(room);
            room.phase = 'review';
            room.timerEnd = null;
        }
        broadcastState(room);
    });

    // ---------- JUST ONE: Confirm review (host) ----------
    socket.on('justone_confirm_review', () => {
        const room = rooms.get(currentRoom);
        if (!room || room.host !== socket.id || room.phase !== 'review') return;
        room.phase = 'guessing';
        room.timerEnd = Date.now() + 90000;
        broadcastState(room);
    });

    // ---------- JUST ONE: Submit guess ----------
    socket.on('justone_guess', ({ guess }) => {
        const room = rooms.get(currentRoom);
        if (!room || room.phase !== 'guessing') return;
        const guesser = room.players[room.guesserIndex];
        if (socket.id !== guesser.id) return;

        room.guess = guess.trim();
        const normalizedGuess = room.guess.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const normalizedWord = room.currentWord.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        room.guessCorrect = normalizedGuess === normalizedWord;

        if (room.guessCorrect) {
            guesser.score += 1;
            room.players.filter(p => p.id !== guesser.id && !room.removedClues.includes(p.id)).forEach(p => p.score += 1);
        }

        room.phase = 'result';
        room.timerEnd = null;
        broadcastState(room);
    });

    // ---------- JUST ONE: Skip guess ----------
    socket.on('justone_skip', () => {
        const room = rooms.get(currentRoom);
        if (!room || room.phase !== 'guessing') return;
        room.guess = '(passou)';
        room.guessCorrect = false;
        room.phase = 'result';
        room.timerEnd = null;
        broadcastState(room);
    });

    // ---------- JUST ONE: Next round ----------
    socket.on('justone_next', () => {
        const room = rooms.get(currentRoom);
        if (!room || room.host !== socket.id) return;
        if (room.round >= room.maxRounds) {
            room.phase = 'gameover';
        } else {
            justoneNewRound(room);
        }
        broadcastState(room);
    });

    // ---------- IMPOSTOR: Ready (seen role) ----------
    socket.on('impostor_ready', () => {
        const room = rooms.get(currentRoom);
        if (!room || room.game !== 'impostor') return;
        if (!room._readyPlayers) room._readyPlayers = new Set();
        room._readyPlayers.add(socket.id);
        if (room._readyPlayers.size >= room.players.length) {
            room.phase = 'discussion';
            room.timerEnd = Date.now() + 120000;
            room._readyPlayers = null;
            broadcastState(room);
        } else {
            broadcastState(room);
        }
    });

    // ---------- IMPOSTOR: Start vote ----------
    socket.on('impostor_start_vote', () => {
        const room = rooms.get(currentRoom);
        if (!room || room.host !== socket.id || room.phase !== 'discussion') return;
        room.phase = 'voting';
        room.timerEnd = Date.now() + 30000;
        broadcastState(room);
    });

    // ---------- IMPOSTOR: Cast vote ----------
    socket.on('impostor_vote', ({ votedFor }) => {
        const room = rooms.get(currentRoom);
        if (!room || room.phase !== 'voting') return;
        room.votes[socket.id] = votedFor;

        if (Object.keys(room.votes).length >= room.players.length) {
            // Count votes
            const voteCounts = {};
            for (const vid of Object.values(room.votes)) {
                voteCounts[vid] = (voteCounts[vid] || 0) + 1;
            }
            const maxVotes = Math.max(...Object.values(voteCounts));
            const mostVoted = Object.keys(voteCounts).filter(k => voteCounts[k] === maxVotes);
            const impostor = room.players[room.impostorIndex];

            room.votedOut = mostVoted.length === 1 ? mostVoted[0] : null;
            room.impostorCaught = mostVoted.length === 1 && mostVoted[0] === impostor.id;

            if (room.impostorCaught) {
                room.phase = 'impostor_guess';
                room.timerEnd = Date.now() + 20000;
            } else {
                // Impostor wins
                impostor.score += 3;
                room.phase = 'result';
            }
            room.timerEnd = null;
            broadcastState(room);
        } else {
            broadcastState(room);
        }
    });

    // ---------- IMPOSTOR: Impostor guesses the word ----------
    socket.on('impostor_final_guess', ({ guess }) => {
        const room = rooms.get(currentRoom);
        if (!room || room.phase !== 'impostor_guess') return;
        const impostor = room.players[room.impostorIndex];
        if (socket.id !== impostor.id) return;

        room.guess = guess.trim();
        const normalizedGuess = room.guess.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const normalizedWord = room.currentWord.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        room.guessCorrect = normalizedGuess === normalizedWord;

        if (room.guessCorrect) {
            impostor.score += 2;
        } else {
            room.players.filter(p => p.id !== impostor.id).forEach(p => p.score += 2);
        }
        room.phase = 'result';
        room.timerEnd = null;
        broadcastState(room);
    });

    // ---------- IMPOSTOR: Next round ----------
    socket.on('impostor_next', () => {
        const room = rooms.get(currentRoom);
        if (!room || room.host !== socket.id) return;
        if (room.round >= room.maxRounds) {
            room.phase = 'gameover';
        } else {
            impostorNewRound(room);
        }
        broadcastState(room);
    });

    // ---------- NEW GAME ----------
    socket.on('new_game', () => {
        const room = rooms.get(currentRoom);
        if (!room || room.host !== socket.id) return;
        room.round = 0;
        room.players.forEach(p => p.score = 0);
        room.phase = 'lobby';
        broadcastState(room);
    });

    // ---------- DISCONNECT ----------
    socket.on('disconnect', () => {
        if (!currentRoom) return;
        const room = rooms.get(currentRoom);
        if (!room) return;

        const player = room.players.find(p => p.id === socket.id);
        if (player) player.connected = false;

        // If all disconnected, delete room after 5 min
        if (room.players.every(p => !p.connected)) {
            setTimeout(() => {
                const r = rooms.get(currentRoom);
                if (r && r.players.every(p => !p.connected)) {
                    rooms.delete(currentRoom);
                }
            }, 300000);
        }

        // Transfer host if host disconnected
        if (room.host === socket.id) {
            const connected = room.players.find(p => p.connected && p.id !== socket.id);
            if (connected) room.host = connected.id;
        }

        broadcastState(room);
    });

    // ---------- HELPERS ----------
    function broadcastState(room) {
        for (const p of room.players) {
            io.to(p.id).emit('state_update', getSafeState(room, p.id));
        }
    }

    function getSafeState(room, playerId) {
        const state = {
            code: room.code,
            game: room.game,
            host: room.host,
            phase: room.phase,
            round: room.round,
            maxRounds: room.maxRounds,
            players: room.players.map(p => ({
                id: p.id,
                name: p.name,
                score: p.score,
                connected: p.connected,
                isHost: p.id === room.host,
            })),
            myId: playerId,
            isHost: playerId === room.host,
            timerEnd: room.timerEnd,
        };

        if (room.game === 'justone') {
            state.guesserIndex = room.guesserIndex;
            state.isGuesser = room.players[room.guesserIndex]?.id === playerId;
            state.myClue = room.clues[playerId] || null;
            state.clueCount = Object.keys(room.clues).length;
            state.totalClueExpected = room.players.filter(p => p.id !== room.players[room.guesserIndex]?.id).length;

            if (room.phase === 'show_word' || room.phase === 'writing') {
                state.word = state.isGuesser ? null : room.currentWord;
            }
            if (room.phase === 'review') {
                state.word = room.currentWord;
                state.allClues = Object.entries(room.clues).map(([pid, clue]) => ({
                    playerName: room.players.find(p => p.id === pid)?.name || '?',
                    clue,
                    removed: room.removedClues.includes(pid),
                }));
            }
            if (room.phase === 'guessing') {
                state.visibleClues = Object.entries(room.clues)
                    .filter(([pid]) => !room.removedClues.includes(pid))
                    .map(([pid, clue]) => ({
                        playerName: room.players.find(p => p.id === pid)?.name || '?',
                        clue,
                    }));
            }
            if (room.phase === 'result' || room.phase === 'gameover') {
                state.word = room.currentWord;
                state.guess = room.guess;
                state.guessCorrect = room.guessCorrect;
                state.allClues = Object.entries(room.clues).map(([pid, clue]) => ({
                    playerName: room.players.find(p => p.id === pid)?.name || '?',
                    clue,
                    removed: room.removedClues.includes(pid),
                }));
            }
        }

        if (room.game === 'impostor') {
            state.impostorIndex = room.phase === 'result' || room.phase === 'gameover' || room.phase === 'impostor_guess' ? room.impostorIndex : -1;
            state.isImpostor = room.players[room.impostorIndex]?.id === playerId;
            state.category = room.category;
            state.readyCount = room._readyPlayers ? room._readyPlayers.size : 0;

            if (room.phase === 'show_role' || room.phase === 'discussion' || room.phase === 'voting') {
                state.word = state.isImpostor ? null : room.currentWord;
            }
            if (room.phase === 'result' || room.phase === 'gameover' || room.phase === 'impostor_guess') {
                state.word = room.currentWord;
                state.impostorCaught = room.impostorCaught;
                state.guess = room.guess;
                state.guessCorrect = room.guessCorrect;
                state.votedOut = room.votedOut;
            }
            state.voteCount = Object.keys(room.votes).length;
            state.myVote = room.votes[playerId] || null;
        }

        return state;
    }
});

// ==================== START SERVER ====================

const PORT = 3333;
server.listen(PORT, '0.0.0.0', () => {
    console.log(`\n🎮 Jogos de Festa a correr em:`);
    console.log(`   Local:  http://localhost:${PORT}`);
    console.log(`   Rede:   http://192.168.1.96:${PORT}\n`);
});
