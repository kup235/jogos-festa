const express = require('express');
const http = require('http');
const fs = require('fs');
const path = require('path');

const app = express();
const server = http.createServer(app);

const ROOMS_DIR = path.join(__dirname, 'rooms');
if (!fs.existsSync(ROOMS_DIR)) fs.mkdirSync(ROOMS_DIR, { recursive: true });

const CURRENT_APP_VERSION = 'v3.0';

app.use(express.urlencoded({ extended: true }));
app.use(express.json());

// ==================== WORD LISTS ====================
// Loaded from external file to keep server.js clean
const wordlists = require('./words.js');
const JUSTONE_WORDS = wordlists.JUSTONE_WORDS;
const IMPOSTOR_CATEGORIES = wordlists.IMPOSTOR_CATEGORIES;

// ==================== HELPERS ====================

function loadRoom(code) {
    const file = path.join(ROOMS_DIR, `${code}.json`);
    if (!fs.existsSync(file)) return null;
    try { return JSON.parse(fs.readFileSync(file, 'utf8')); }
    catch { return null; }
}

function saveRoom(room) {
    fs.writeFileSync(path.join(ROOMS_DIR, `${room.code}.json`), JSON.stringify(room), 'utf8');
}

function generateCode() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let code;
    do {
        code = '';
        for (let i = 0; i < 4; i++) code += chars[Math.floor(Math.random() * chars.length)];
    } while (fs.existsSync(path.join(ROOMS_DIR, `${code}.json`)));
    return code;
}

function normalize(s) {
    s = s.toLowerCase().trim();
    const map = {'á':'a','à':'a','ã':'a','â':'a','ä':'a','é':'e','è':'e','ê':'e','ë':'e','í':'i','ì':'i','î':'i','ï':'i','ó':'o','ò':'o','õ':'o','ô':'o','ö':'o','ú':'u','ù':'u','û':'u','ü':'u','ç':'c','ñ':'n'};
    return s.split('').map(c => map[c] || c).join('');
}

function getStem(word) {
    let s = normalize(word);
    if (s.length <= 3) return s;
    const suffixes = ['mente','ções','ação','ismo','ista','ável','ível','ando','endo','indo','ados','idos','adas','idas','ores','eira','eiro','inha','inho','ões','ais','eis','ado','ido','ada','ida','oso','osa','dor','tor','ção','zes','es','ns','is','os','as','ar','er','ir','s'];
    for (const suf of suffixes) {
        if (s.length > suf.length + 2 && s.endsWith(suf)) return s.slice(0, -suf.length);
    }
    return s;
}

function matchesWord(guess, word) {
    const ng = normalize(guess), nw = normalize(word);
    if (ng === nw) return true;
    if (ng + 's' === nw || nw + 's' === ng) return true;
    if (ng + 'es' === nw || nw + 'es' === ng) return true;
    if (nw.endsWith('ao') && ng === nw.slice(0,-2) + 'oes') return true;
    if (ng.endsWith('ao') && nw === ng.slice(0,-2) + 'oes') return true;
    if (nw.endsWith('ao') && ng === nw.slice(0,-2) + 'aes') return true;
    if (ng.endsWith('ao') && nw === ng.slice(0,-2) + 'aes') return true;
    if (nw.endsWith('al') && ng === nw.slice(0,-1) + 'is') return true;
    if (ng.endsWith('al') && nw === ng.slice(0,-1) + 'is') return true;
    if (nw.endsWith('el') && ng === nw.slice(0,-1) + 'is') return true;
    if (ng.endsWith('el') && nw === ng.slice(0,-1) + 'is') return true;
    if (nw.endsWith('il') && ng === nw.slice(0,-2) + 'is') return true;
    if (ng.endsWith('il') && nw === ng.slice(0,-2) + 'is') return true;
    if (ng.length >= 3 && nw.length >= 3 && getStem(guess) === getStem(word)) return true;
    return false;
}

function isDerivative(clue, word) {
    const nc = normalize(clue), nw = normalize(word);
    if (nc.length < 3 || nw.length < 3) return false;
    if (nc === nw) return true;
    if (nc.includes(nw) || nw.includes(nc)) return true;
    const sc = getStem(clue), sw = getStem(word);
    if (sc.length >= 3 && sw.length >= 3 && sc === sw) return true;
    return false;
}

function pickRandom(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

function loadUsedWords(filename) {
    const file = path.join(ROOMS_DIR, filename);
    if (!fs.existsSync(file)) return {};
    try {
        const data = JSON.parse(fs.readFileSync(file, 'utf8'));
        const cutoff = Math.floor(Date.now() / 1000) - 86400;
        const clean = {};
        for (const [w, ts] of Object.entries(data)) { if (ts > cutoff) clean[w] = ts; }
        return clean;
    } catch { return {}; }
}

function saveUsedWords(filename, words) {
    fs.writeFileSync(path.join(ROOMS_DIR, filename), JSON.stringify(words), 'utf8');
}

// ==================== GAME LOGIC ====================

function justoneNewRound(room) {
    room.round++;
    room.phase = 'pick_number';
    const n = room.players.length;
    if (room.guesserIndex < 0) room.guesserIndex = Math.floor(Math.random() * n);
    else room.guesserIndex = (room.guesserIndex + 1) % n;
    const usedWords = loadUsedWords('used_words_justone.json');
    const diff = room.difficulty || 'medium';
    let words = JUSTONE_WORDS[diff] || JUSTONE_WORDS.medium;
    let available = words.filter(w => !usedWords[w.toLowerCase()]);
    if (available.length < 5) { available = words; Object.keys(usedWords).forEach(k => delete usedWords[k]); }
    const copy = [...available]; room.wordCard = [];
    for (let i = 0; i < 5 && copy.length > 0; i++) {
        const idx = Math.floor(Math.random() * copy.length);
        room.wordCard.push(copy[idx]);
        usedWords[copy[idx].toLowerCase()] = Math.floor(Date.now() / 1000);
        copy.splice(idx, 1);
    }
    saveUsedWords('used_words_justone.json', usedWords);
    room.chosenNumber = null; room.currentWord = null; room.clues = {};
    room.removedClues = []; room.guess = null; room.guessCorrect = null;
    room.timerEnd = null; room.readyPlayers = [];
}

function justoneCheckDuplicates(room) {
    room.removedClues = [];
    for (const [pid, clue] of Object.entries(room.clues)) {
        if (isDerivative(clue, room.currentWord)) room.removedClues.push(pid);
    }
    if (room.removeDuplicates !== false) {
        const clueMap = {};
        for (const [pid, clue] of Object.entries(room.clues)) {
            if (room.removedClues.includes(pid)) continue;
            const norm = normalize(clue);
            if (!clueMap[norm]) clueMap[norm] = [];
            clueMap[norm].push(pid);
        }
        for (const pids of Object.values(clueMap)) { if (pids.length > 1) room.removedClues.push(...pids); }
        const stems = {};
        for (const [pid, clue] of Object.entries(room.clues)) {
            if (room.removedClues.includes(pid)) continue;
            const stem = getStem(clue);
            if (!stems[stem]) stems[stem] = [];
            stems[stem].push(pid);
        }
        for (const pids of Object.values(stems)) {
            if (pids.length > 1) { for (const pid of pids) { if (!room.removedClues.includes(pid)) room.removedClues.push(pid); } }
        }
    }
}

function impostorNewRound(room) {
    room.round++;
    room.phase = 'show_role';
    const diff = room.difficulty || 'medium';
    const cats = IMPOSTOR_CATEGORIES[diff] || IMPOSTOR_CATEGORIES.medium;
    const usedWords = loadUsedWords('used_words_impostor.json');
    let attempts = 0, cat, availableWords;
    do {
        cat = pickRandom(cats);
        availableWords = cat.palavras.filter(w => !usedWords[w.toLowerCase()]);
        attempts++;
        if (attempts > 20) { Object.keys(usedWords).forEach(k => delete usedWords[k]); availableWords = cat.palavras; }
    } while (availableWords.length === 0 && attempts <= 20);
    const chosenWord = pickRandom(availableWords);
    usedWords[chosenWord.toLowerCase()] = Math.floor(Date.now() / 1000);
    saveUsedWords('used_words_impostor.json', usedWords);
    room.category = cat.categoria; room.currentWord = chosenWord;
    room.impostorIndex = Math.floor(Math.random() * room.players.length);
    room.readyPlayers = []; room.votes = {}; room.guess = null;
    room.guessCorrect = null; room.impostorCaught = false; room.votedOut = null; room.timerEnd = null;
}

// ==================== GET SAFE STATE ====================

function getSafeState(room, playerId) {
    const active = room.activePlayers || [];
    const isSpectator = room.phase !== 'lobby' && active.length > 0 && !active.includes(playerId);
    const state = {
        code: room.code, game: room.game, host: room.host,
        difficulty: room.difficulty || 'medium', removeDuplicates: room.removeDuplicates !== false,
        phase: isSpectator ? 'spectator' : room.phase, round: room.round, maxRounds: room.maxRounds,
        myId: playerId, isHost: playerId === room.host, serverVersion: CURRENT_APP_VERSION,
        timerEnd: room.timerEnd || null,
        players: room.players.map(p => ({ id: p.id, name: p.name, score: p.score, isHost: p.id === room.host, version: p.version || null })),
    };
    if (room.game === 'justone') {
        const guesserIdx = room.guesserIndex ?? -1;
        const guesserId = room.players[guesserIdx]?.id || '';
        state.guesserIndex = guesserIdx;
        state.isGuesser = guesserId === playerId;
        const nonGuessers = room.players.filter(p => p.id !== guesserId);
        if (!state.isGuesser) { state.clueCount = Object.keys(room.clues || {}).length; state.totalClueExpected = nonGuessers.length; }
        if (room.phase === 'pick_number') state.wordCard = state.isGuesser ? null : (room.wordCard || []);
        if (room.phase === 'show_word') {
            state.word = state.isGuesser ? null : room.currentWord;
            state.chosenNumber = room.chosenNumber || null;
            state.readyCount = (room.readyPlayers || []).length;
            state.isReady = (room.readyPlayers || []).includes(playerId);
        }
        if (room.phase === 'writing') { state.word = state.isGuesser ? null : room.currentWord; state.myClue = (room.clues || {})[playerId] || null; }
        if (room.phase === 'review') {
            if (!state.isGuesser) {
                state.word = room.currentWord;
                state.allClues = Object.entries(room.clues || {}).map(([pid, clue]) => ({ playerName: room.players.find(p => p.id === pid)?.name || '?', clue, removed: (room.removedClues || []).includes(pid) }));
            } else { state.word = null; state.allClues = []; }
        }
        if (room.phase === 'guessing') {
            state.visibleClues = Object.entries(room.clues || {}).filter(([pid]) => !(room.removedClues || []).includes(pid)).map(([pid, clue]) => ({ playerName: room.players.find(p => p.id === pid)?.name || '?', clue }));
            if (!state.isGuesser) {
                state.word = room.currentWord;
                state.allClues = Object.entries(room.clues || {}).map(([pid, clue]) => ({ playerName: room.players.find(p => p.id === pid)?.name || '?', clue, removed: (room.removedClues || []).includes(pid) }));
            }
        }
        if (['result', 'gameover'].includes(room.phase)) {
            state.word = room.currentWord; state.guess = room.guess; state.guessCorrect = room.guessCorrect;
            state.allClues = Object.entries(room.clues || {}).map(([pid, clue]) => ({ playerName: room.players.find(p => p.id === pid)?.name || '?', clue, removed: (room.removedClues || []).includes(pid) }));
        }
    }
    if (room.game === 'impostor') {
        const impIdx = room.impostorIndex ?? -1;
        const impId = room.players[impIdx]?.id || '';
        state.isImpostor = impId === playerId;
        const diff = room.difficulty || 'medium';
        state.category = (state.isImpostor && diff !== 'easy') ? null : (room.category || null);
        state.readyCount = (room.readyPlayers || []).length;
        state.voteCount = Object.keys(room.votes || {}).length;
        state.myVote = (room.votes || {})[playerId] || null;
        state.impostorIndex = ['result', 'gameover', 'impostor_guess'].includes(room.phase) ? impIdx : -1;
        if (['show_role', 'discussion', 'voting'].includes(room.phase)) state.word = state.isImpostor ? null : room.currentWord;
        if (room.phase === 'impostor_guess') {
            state.word = state.isImpostor ? null : room.currentWord;
            state.impostorCaught = room.impostorCaught || false;
            state.guess = room.guess; state.guessCorrect = room.guessCorrect; state.votedOut = room.votedOut || null;
        }
        if (['result', 'gameover'].includes(room.phase)) {
            state.word = room.currentWord; state.impostorCaught = room.impostorCaught || false;
            state.guess = room.guess; state.guessCorrect = room.guessCorrect; state.votedOut = room.votedOut || null;
        }
    }
    return state;
}

// ==================== CLEANUP ====================
function cleanupRooms() {
    try {
        const files = fs.readdirSync(ROOMS_DIR).filter(f => f.endsWith('.json') && !f.startsWith('used_words'));
        const cutoff = Date.now() - 7200000;
        for (const f of files) { const fp = path.join(ROOMS_DIR, f); if (fs.statSync(fp).mtimeMs < cutoff) fs.unlinkSync(fp); }
    } catch {}
}
cleanupRooms();

// ==================== API ROUTE ====================

function handleApi(req, res) {
    res.setHeader('Content-Type', 'application/json; charset=utf-8');
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Cache-Control', 'no-store');
    const p = { ...req.query, ...req.body };
    const action = p.action || '';
    const playerId = p.player_id || '';
    const roomCode = (p.room_code || '').toUpperCase().trim();
    function respond(data) { res.json(data); }
    function error(msg) { respond({ ok: false, error: msg }); }
    const now = () => Date.now();

    switch (action) {
    case 'create_room': {
        const game = p.game || ''; const name = (p.name || '').trim();
        if (!name) return error('Escreve o teu nome!');
        if (!['justone', 'impostor'].includes(game)) return error('Jogo inválido!');
        let difficulty = p.difficulty || 'medium';
        if (!['easy','medium','hard'].includes(difficulty)) difficulty = 'medium';
        const code = generateCode();
        const room = { code, game, host: playerId, difficulty, players: [{ id: playerId, name, score: 0 }], phase: 'lobby', round: 0, maxRounds: 5, removeDuplicates: true, guesserIndex: -1, currentWord: null, clues: {}, removedClues: [], guess: null, guessCorrect: null, category: null, impostorIndex: -1, readyPlayers: [], votes: {}, impostorCaught: false, votedOut: null, timerEnd: null };
        saveRoom(room); respond({ ok: true, code, state: getSafeState(room, playerId) }); break;
    }
    case 'join_room': {
        const name = (p.name || '').trim();
        if (!name) return error('Escreve o teu nome!');
        if (roomCode.length !== 4) return error('Código deve ter 4 letras!');
        const room = loadRoom(roomCode);
        if (!room) return error('Sala não encontrada!');
        let found = false;
        for (const pl of room.players) { if (pl.id === playerId) { pl.name = name; found = true; break; } }
        if (!found) {
            if (room.players.find(pl => pl.name === name)) return error('Já existe alguém com esse nome!');
            room.players.push({ id: playerId, name, score: 0 });
        }
        saveRoom(room); respond({ ok: true, code: roomCode, game: room.game, state: getSafeState(room, playerId) }); break;
    }
    case 'get_state': {
        if (!roomCode) return error('Código em falta!');
        let room = loadRoom(roomCode);
        if (!room) return error('Sala não encontrada!');
        let playerFound = false;
        for (const pp of room.players) { if (pp.id === playerId) { playerFound = true; if (p.app_version && (pp.version || '') !== p.app_version) { pp.version = p.app_version; saveRoom(room); } break; } }
        if (!playerFound) return error('Não estás nesta sala!');
        if (room.timerEnd && now() > room.timerEnd) {
            let changed = false;
            if (room.game === 'justone' && room.phase === 'writing') { justoneCheckDuplicates(room); room.phase = 'guessing'; room.timerEnd = now() + 90000; changed = true; }
            else if (room.game === 'justone' && room.phase === 'guessing') { room.guess = '(tempo esgotado)'; room.guessCorrect = false; room.phase = 'result'; room.timerEnd = null; changed = true; }
            else if (room.game === 'justone' && room.phase === 'result' && room.guessCorrect) { if (room.round >= room.maxRounds) room.phase = 'gameover'; else justoneNewRound(room); room.timerEnd = null; changed = true; }
            else if (room.game === 'impostor' && room.phase === 'discussion') { room.phase = 'voting'; room.timerEnd = now() + 30000; changed = true; }
            if (changed) saveRoom(room);
        }
        respond({ ok: true, state: getSafeState(room, playerId) }); break;
    }
    case 'set_difficulty': {
        let difficulty = p.difficulty || 'medium';
        if (!['easy','medium','hard'].includes(difficulty)) return error('Dificuldade inválida!');
        const room = loadRoom(roomCode); if (!room) return error('Sala não encontrada!');
        if (room.host !== playerId) return error('Só o anfitrião pode mudar!');
        room.difficulty = difficulty; saveRoom(room); respond({ ok: true }); break;
    }
    case 'set_rounds': {
        const rounds = parseInt(p.rounds) || 5;
        if (rounds < 1 || rounds > 20) return error('Número de rondas inválido!');
        const room = loadRoom(roomCode); if (!room) return error('Sala não encontrada!');
        if (room.host !== playerId) return error('Só o anfitrião pode mudar!');
        room.maxRounds = rounds; saveRoom(room); respond({ ok: true }); break;
    }
    case 'set_remove_duplicates': {
        const remove = p.remove_duplicates !== 'false' && p.remove_duplicates !== false;
        const room = loadRoom(roomCode); if (!room) return error('Sala não encontrada!');
        if (room.host !== playerId) return error('Só o anfitrião pode mudar!');
        room.removeDuplicates = remove; saveRoom(room); respond({ ok: true }); break;
    }
    case 'start_game': {
        const room = loadRoom(roomCode); if (!room) return error('Sala não encontrada!');
        if (room.host !== playerId) return error('Só o anfitrião pode começar!');
        const minPlayers = room.game === 'justone' ? 2 : 3;
        if (room.players.length < minPlayers) return error(`Precisas de pelo menos ${minPlayers} jogadores!`);
        room.activePlayers = room.players.map(pl => pl.id);
        if (room.game === 'justone') justoneNewRound(room); else impostorNewRound(room);
        saveRoom(room); respond({ ok: true, state: getSafeState(room, playerId) }); break;
    }
    case 'justone_pick_number': {
        const number = parseInt(p.number) || 0;
        if (number < 1 || number > 5) return error('Escolhe um número de 1 a 5!');
        const room = loadRoom(roomCode); if (!room || room.phase !== 'pick_number') return error('Ação inválida!');
        if (playerId !== (room.players[room.guesserIndex]?.id || '')) return error('Só o adivinhador pode escolher!');
        room.chosenNumber = number; room.currentWord = room.wordCard[number - 1]; room.phase = 'show_word';
        saveRoom(room); respond({ ok: true }); break;
    }
    case 'justone_ready': {
        const room = loadRoom(roomCode); if (!room || room.phase !== 'show_word') return error('Ação inválida!');
        const guesserId = room.players[room.guesserIndex]?.id || '';
        if (playerId !== guesserId && !room.readyPlayers.includes(playerId)) room.readyPlayers.push(playerId);
        const nonGuessers = room.players.filter(pl => pl.id !== guesserId);
        if (room.readyPlayers.length >= nonGuessers.length) { room.phase = 'writing'; room.timerEnd = now() + 60000; room.readyPlayers = []; }
        saveRoom(room); respond({ ok: true }); break;
    }
    case 'justone_clue': {
        const clue = (p.clue || '').trim();
        if (!clue) return error('Escreve uma pista!');
        if (clue.includes(' ')) return error('Apenas uma palavra!');
        const room = loadRoom(roomCode); if (!room || room.phase !== 'writing') return error('Ação inválida!');
        const guesserId = room.players[room.guesserIndex]?.id || '';
        if (playerId === guesserId) return error('Tu és o adivinhador!');
        if (isDerivative(clue, room.currentWord)) return error('Não podes usar a palavra secreta nem derivados dela!');
        room.clues[playerId] = clue;
        const nonGuessers = room.players.filter(pl => pl.id !== guesserId);
        if (Object.keys(room.clues).length >= nonGuessers.length) { justoneCheckDuplicates(room); room.phase = 'review'; room.timerEnd = null; }
        saveRoom(room); respond({ ok: true }); break;
    }
    case 'justone_confirm_review': {
        const room = loadRoom(roomCode); if (!room || room.host !== playerId || room.phase !== 'review') return error('Ação inválida!');
        room.phase = 'guessing'; room.timerEnd = now() + 90000; saveRoom(room); respond({ ok: true }); break;
    }
    case 'justone_guess': {
        const guess = (p.guess || '').trim(); if (!guess) return error('Escreve a tua resposta!');
        const room = loadRoom(roomCode); if (!room || room.phase !== 'guessing') return error('Ação inválida!');
        const guesserId = room.players[room.guesserIndex]?.id || '';
        if (playerId !== guesserId) return error('Não és o adivinhador!');
        room.guess = guess; room.guessCorrect = matchesWord(guess, room.currentWord);
        if (room.guessCorrect) { for (const pl of room.players) { if (pl.id === guesserId) pl.score += 1; else if (!room.removedClues.includes(pl.id)) pl.score += 1; } }
        room.phase = 'result'; room.timerEnd = room.guessCorrect ? now() + 5000 : null;
        saveRoom(room); respond({ ok: true }); break;
    }
    case 'justone_skip': {
        const room = loadRoom(roomCode); if (!room || room.phase !== 'guessing') return error('Ação inválida!');
        const guesserId = room.players[room.guesserIndex]?.id || '';
        if (playerId !== guesserId) return error('Só o adivinhador pode passar!');
        room.guess = '(passou)'; room.guessCorrect = false; room.phase = 'result'; room.timerEnd = null;
        saveRoom(room); respond({ ok: true }); break;
    }
    case 'next_round': {
        const room = loadRoom(roomCode); if (!room || room.host !== playerId) return error('Ação inválida!');
        if (room.phase !== 'result') return respond({ ok: true });
        if (room.round >= room.maxRounds) room.phase = 'gameover';
        else { if (room.game === 'justone') justoneNewRound(room); else impostorNewRound(room); }
        saveRoom(room); respond({ ok: true }); break;
    }
    case 'impostor_ready': {
        const room = loadRoom(roomCode); if (!room || room.phase !== 'show_role') return error('Ação inválida!');
        if (!room.readyPlayers.includes(playerId)) room.readyPlayers.push(playerId);
        if (room.readyPlayers.length >= room.players.length) { room.phase = 'discussion'; room.timerEnd = now() + 120000; room.readyPlayers = []; }
        saveRoom(room); respond({ ok: true }); break;
    }
    case 'impostor_start_vote': {
        const room = loadRoom(roomCode); if (!room || room.host !== playerId || room.phase !== 'discussion') return error('Ação inválida!');
        room.phase = 'voting'; room.timerEnd = now() + 30000; saveRoom(room); respond({ ok: true }); break;
    }
    case 'impostor_vote': {
        const votedFor = p.voted_for || '';
        const room = loadRoom(roomCode); if (!room || room.phase !== 'voting') return error('Ação inválida!');
        room.votes[playerId] = votedFor;
        if (Object.keys(room.votes).length >= room.players.length) {
            const voteCounts = {}; for (const vid of Object.values(room.votes)) voteCounts[vid] = (voteCounts[vid] || 0) + 1;
            const maxVotes = Math.max(...Object.values(voteCounts));
            const mostVoted = Object.keys(voteCounts).filter(k => voteCounts[k] === maxVotes);
            const impostorId = room.players[room.impostorIndex]?.id || '';
            room.votedOut = mostVoted.length === 1 ? mostVoted[0] : null;
            room.impostorCaught = mostVoted.length === 1 && mostVoted[0] === impostorId;
            if (room.impostorCaught) room.phase = 'impostor_guess';
            else { for (const pl of room.players) { if (pl.id === impostorId) pl.score += 3; } room.phase = 'result'; }
            room.timerEnd = null;
        }
        saveRoom(room); respond({ ok: true }); break;
    }
    case 'impostor_final_guess': {
        const guess = (p.guess || '').trim();
        const room = loadRoom(roomCode); if (!room || room.phase !== 'impostor_guess') return error('Ação inválida!');
        const impostorId = room.players[room.impostorIndex]?.id || '';
        if (playerId !== impostorId) return error('Não és o impostor!');
        room.guess = guess; room.guessCorrect = matchesWord(guess, room.currentWord);
        if (room.guessCorrect) { for (const pl of room.players) { if (pl.id === impostorId) pl.score += 2; } }
        else { for (const pl of room.players) { if (pl.id !== impostorId) pl.score += 2; } }
        room.phase = 'result'; room.timerEnd = null; saveRoom(room); respond({ ok: true }); break;
    }
    case 'leave_room': {
        const room = loadRoom(roomCode); if (!room) return error('Sala não encontrada!');
        const guesserIdx = room.guesserIndex ?? -1;
        const oldGuesserId = room.players[guesserIdx]?.id || null;
        room.players = room.players.filter(pl => pl.id !== playerId);
        if (room.players.length === 0) {
            const file = path.join(ROOMS_DIR, `${roomCode}.json`);
            if (fs.existsSync(file)) fs.unlinkSync(file);
            return respond({ ok: true, deleted: true });
        }
        if (room.host === playerId) room.host = room.players[0].id;
        if (room.activePlayers) room.activePlayers = room.activePlayers.filter(id => id !== playerId);
        if (room.readyPlayers) room.readyPlayers = room.readyPlayers.filter(id => id !== playerId);
        if (room.clues) delete room.clues[playerId];
        if (room.votes) delete room.votes[playerId];
        if (room.phase !== 'lobby' && room.players.length < 2) {
            room.phase = 'lobby'; room.round = 0; room.guesserIndex = -1; room.timerEnd = null;
            room.readyPlayers = []; room.wordCard = []; room.chosenNumber = null; room.currentWord = null;
            room.clues = {}; room.removedClues = []; room.guess = null; room.guessCorrect = null;
            room.impostorIndex = -1; room.category = null; room.votes = {}; room.activePlayers = [];
        } else if (room.phase !== 'lobby' && room.players.length >= 2) {
            if (room.game === 'justone') {
                if (oldGuesserId && oldGuesserId === playerId) {
                    if (guesserIdx >= room.players.length) room.guesserIndex = room.players.length - 1;
                    room.round--; justoneNewRound(room);
                } else {
                    let newGuesserIdx = -1;
                    for (let i = 0; i < room.players.length; i++) { if (room.players[i].id === oldGuesserId) { newGuesserIdx = i; break; } }
                    if (newGuesserIdx >= 0) room.guesserIndex = newGuesserIdx;
                    const nonGuessers = room.players.filter(pl => pl.id !== oldGuesserId);
                    if (room.phase === 'show_word' && room.readyPlayers.length >= nonGuessers.length) { room.phase = 'writing'; room.timerEnd = now() + 60000; room.readyPlayers = []; }
                    if (room.phase === 'writing' && Object.keys(room.clues || {}).length >= nonGuessers.length) { justoneCheckDuplicates(room); room.phase = 'review'; room.timerEnd = null; }
                }
            }
            if (room.game === 'impostor') {
                if (room.phase === 'show_role' && room.readyPlayers.length >= room.players.length) { room.phase = 'discussion'; room.timerEnd = now() + 120000; room.readyPlayers = []; }
                if (room.phase === 'voting' && Object.keys(room.votes || {}).length >= room.players.length) {
                    const voteCounts = {}; for (const vid of Object.values(room.votes)) voteCounts[vid] = (voteCounts[vid] || 0) + 1;
                    const maxVotes = Math.max(...Object.values(voteCounts));
                    const mostVoted = Object.keys(voteCounts).filter(k => voteCounts[k] === maxVotes);
                    const impostorId = room.players[room.impostorIndex]?.id || '';
                    room.votedOut = mostVoted.length === 1 ? mostVoted[0] : null;
                    room.impostorCaught = mostVoted.length === 1 && mostVoted[0] === impostorId;
                    if (room.impostorCaught) room.phase = 'impostor_guess';
                    else { for (const pl of room.players) { if (pl.id === impostorId) pl.score += 3; } room.phase = 'result'; }
                    room.timerEnd = null;
                }
                if (room.phase === 'impostor_guess') {
                    const impostorId = room.players[room.impostorIndex]?.id || null;
                    if (!impostorId) { room.guess = '(impostor saiu)'; room.guessCorrect = false; for (const pl of room.players) pl.score += 2; room.phase = 'result'; room.timerEnd = null; }
                }
            }
        }
        saveRoom(room); respond({ ok: true, newHost: room.host }); break;
    }
    case 'new_game': {
        const room = loadRoom(roomCode); if (!room || room.host !== playerId) return error('Ação inválida!');
        room.round = 0; room.guesserIndex = -1; for (const pl of room.players) pl.score = 0;
        room.phase = 'lobby'; room.timerEnd = null; room.readyPlayers = [];
        room.wordCard = []; room.chosenNumber = null; room.currentWord = null;
        room.clues = {}; room.removedClues = []; room.guess = null; room.guessCorrect = null;
        room.impostorIndex = -1; room.category = null; room.votes = {};
        saveRoom(room); respond({ ok: true }); break;
    }
    case 'restart_game': {
        const room = loadRoom(roomCode); if (!room || room.host !== playerId) return error('Ação inválida!');
        if (p.difficulty && ['easy','medium','hard'].includes(p.difficulty)) room.difficulty = p.difficulty;
        room.round = 0; room.guesserIndex = -1; for (const pl of room.players) pl.score = 0;
        room.activePlayers = room.players.map(pl => pl.id);
        room.timerEnd = null; room.readyPlayers = [];
        room.wordCard = []; room.chosenNumber = null; room.currentWord = null;
        room.clues = {}; room.removedClues = []; room.guess = null; room.guessCorrect = null;
        room.impostorIndex = -1; room.category = null; room.votes = {};
        if (room.game === 'justone') justoneNewRound(room); else if (room.game === 'impostor') impostorNewRound(room);
        saveRoom(room); respond({ ok: true }); break;
    }
    default: error('Ação desconhecida: ' + action);
    }
}

app.get('/api.php', handleApi);
app.post('/api.php', handleApi);

// ==================== ICON ROUTE (replaces icon.php) ====================
app.get('/icon.php', (req, res) => {
    const size = parseInt(req.query.s) || 192;
    const file = path.join(__dirname, 'icons', `icon-${size === 512 ? 512 : 192}.png`);
    if (fs.existsSync(file)) { res.setHeader('Content-Type', 'image/png'); res.setHeader('Cache-Control', 'public, max-age=604800'); return res.sendFile(file); }
    const svg = path.join(__dirname, 'icons', `icon-${size === 512 ? 512 : 192}.svg`);
    if (fs.existsSync(svg)) { res.setHeader('Content-Type', 'image/svg+xml'); return res.sendFile(svg); }
    res.status(404).end();
});

// ==================== STATIC FILES (serve from root, NOT public/) ====================
app.use(express.static(__dirname, { extensions: ['html'], index: 'index.html' }));

// ==================== START SERVER ====================
const PORT = 3333;
server.listen(PORT, '0.0.0.0', () => {
    console.log(`\n🎮 Jogos de Festa a correr em:`);
    console.log(`   Local:  http://localhost:${PORT}`);
    console.log(`   Rede:   http://0.0.0.0:${PORT}\n`);
});
