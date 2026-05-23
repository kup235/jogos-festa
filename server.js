const express = require('express');
const http = require('http');
const path = require('path');
const fs = require('fs');

const app = express();
const server = http.createServer(app);

app.use(express.urlencoded({ extended: true }));
app.use(express.json());

const roomsDir = path.join(__dirname, 'rooms');
if (!fs.existsSync(roomsDir)) fs.mkdirSync(roomsDir, { recursive: true });

const CURRENT_APP_VERSION = 'v3.0';

// ==================== WORD LISTS ====================
// Loaded from external file to keep server.js clean
const wordData = require('./words.js');
const JUSTONE_WORDS = wordData.JUSTONE_WORDS;
const IMPOSTOR_CATEGORIES = wordData.IMPOSTOR_CATEGORIES;

// ==================== HELPERS ====================

function loadRoom(code) {
    const file = path.join(roomsDir, `${code}.json`);
    if (!fs.existsSync(file)) return null;
    try { return JSON.parse(fs.readFileSync(file, 'utf-8')); }
    catch { return null; }
}

function saveRoom(room) {
    const file = path.join(roomsDir, `${room.code}.json`);
    fs.writeFileSync(file, JSON.stringify(room), 'utf-8');
}

function generateCode() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let code;
    do {
        code = '';
        for (let i = 0; i < 4; i++) code += chars[Math.floor(Math.random() * chars.length)];
    } while (fs.existsSync(path.join(roomsDir, `${code}.json`)));
    return code;
}

function normalize(s) {
    s = s.toLowerCase().trim();
    const map = {'á':'a','à':'a','ã':'a','â':'a','ä':'a','é':'e','è':'e','ê':'e','ë':'e','í':'i','ì':'i','î':'i','ï':'i','ó':'o','ò':'o','õ':'o','ô':'o','ö':'o','ú':'u','ù':'u','û':'u','ü':'u','ç':'c','ñ':'n'};
    for (const [from, to] of Object.entries(map)) s = s.split(from).join(to);
    return s;
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
    if (nw.endsWith('ao') && ng === nw.slice(0,-2)+'oes') return true;
    if (ng.endsWith('ao') && nw === ng.slice(0,-2)+'oes') return true;
    if (nw.endsWith('ao') && ng === nw.slice(0,-2)+'aes') return true;
    if (ng.endsWith('ao') && nw === ng.slice(0,-2)+'aes') return true;
    if (nw.endsWith('al') && ng === nw.slice(0,-1)+'is') return true;
    if (ng.endsWith('al') && nw === ng.slice(0,-1)+'is') return true;
    if (nw.endsWith('el') && ng === nw.slice(0,-1)+'is') return true;
    if (ng.endsWith('el') && nw === ng.slice(0,-1)+'is') return true;
    if (nw.endsWith('il') && ng === nw.slice(0,-2)+'is') return true;
    if (ng.endsWith('il') && nw === ng.slice(0,-2)+'is') return true;
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
function randomInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }

function loadUsedWords(game) {
    const file = path.join(roomsDir, `used_words_${game}.json`);
    if (!fs.existsSync(file)) return {};
    try {
        const data = JSON.parse(fs.readFileSync(file, 'utf-8'));
        const cutoff = Math.floor(Date.now()/1000) - 86400;
        const filtered = {};
        for (const [w, ts] of Object.entries(data)) { if (ts > cutoff) filtered[w] = ts; }
        return filtered;
    } catch { return {}; }
}

function saveUsedWords(game, words) {
    fs.writeFileSync(path.join(roomsDir, `used_words_${game}.json`), JSON.stringify(words), 'utf-8');
}

// ==================== GAME LOGIC ====================

function justoneNewRound(room) {
    room.round++;
    room.phase = 'pick_number';
    const n = room.players.length;
    if (room.guesserIndex < 0) room.guesserIndex = randomInt(0, n-1);
    else room.guesserIndex = (room.guesserIndex + 1) % n;

    let usedWords = loadUsedWords('justone');
    const diff = room.difficulty || 'medium';
    let words = JUSTONE_WORDS[diff] || JUSTONE_WORDS.medium;
    let available = words.filter(w => !usedWords[w.toLowerCase()]);
    if (available.length < 5) { available = words; usedWords = {}; }

    const used = new Set();
    room.wordCard = [];
    while (room.wordCard.length < 5) {
        const idx = Math.floor(Math.random() * available.length);
        if (!used.has(idx)) { used.add(idx); room.wordCard.push(available[idx]); }
    }
    for (const w of room.wordCard) usedWords[w.toLowerCase()] = Math.floor(Date.now()/1000);
    saveUsedWords('justone', usedWords);

    room.chosenNumber = null; room.currentWord = null;
    room.clues = {}; room.removedClues = [];
    room.guess = null; room.guessCorrect = null;
    room.timerEnd = null; room.readyPlayers = [];
}

function justoneCheckDuplicates(room) {
    room.removedClues = [];
    const removeDup = room.removeDuplicates !== false;
    for (const [pid, clue] of Object.entries(room.clues)) {
        if (isDerivative(clue, room.currentWord)) room.removedClues.push(pid);
    }
    if (removeDup) {
        const clueMap = {};
        for (const [pid, clue] of Object.entries(room.clues)) {
            if (room.removedClues.includes(pid)) continue;
            const n = normalize(clue);
            if (!clueMap[n]) clueMap[n] = [];
            clueMap[n].push(pid);
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
            if (pids.length > 1) for (const pid of pids) { if (!room.removedClues.includes(pid)) room.removedClues.push(pid); }
        }
    }
}

function impostorNewRound(room) {
    room.round++;
    room.phase = 'show_role';
    const diff = room.difficulty || 'medium';
    const cats = IMPOSTOR_CATEGORIES[diff] || IMPOSTOR_CATEGORIES.medium;
    let usedWords = loadUsedWords('impostor');
    let attempts = 0, cat, availableWords;
    do {
        cat = pickRandom(cats);
        availableWords = cat.palavras.filter(w => !usedWords[w.toLowerCase()]);
        attempts++;
        if (attempts > 20) { usedWords = {}; availableWords = cat.palavras; }
    } while (availableWords.length === 0 && attempts <= 20);
    const chosenWord = pickRandom(availableWords);
    usedWords[chosenWord.toLowerCase()] = Math.floor(Date.now()/1000);
    saveUsedWords('impostor', usedWords);
    room.category = cat.categoria; room.currentWord = chosenWord;
    room.impostorIndex = randomInt(0, room.players.length - 1);
    room.readyPlayers = []; room.votes = {};
    room.guess = null; room.guessCorrect = null;
    room.impostorCaught = false; room.votedOut = null; room.timerEnd = null;
}

// ==================== SAFE STATE ====================

function getSafeState(room, playerId) {
    const active = room.activePlayers || [];
    const isSpectator = room.phase !== 'lobby' && active.length > 0 && !active.includes(playerId);
    const state = {
        code: room.code, game: room.game, host: room.host,
        difficulty: room.difficulty || 'medium', removeDuplicates: room.removeDuplicates !== false,
        phase: isSpectator ? 'spectator' : room.phase,
        round: room.round, maxRounds: room.maxRounds,
        myId: playerId, isHost: playerId === room.host,
        serverVersion: CURRENT_APP_VERSION, timerEnd: room.timerEnd || null,
        players: room.players.map(p => ({ id: p.id, name: p.name, score: p.score, isHost: p.id === room.host, version: p.version || null })),
    };
    if (room.game === 'justone') {
        const gIdx = room.guesserIndex >= 0 ? room.guesserIndex : -1;
        const gId = (room.players[gIdx]||{}).id || '';
        state.guesserIndex = gIdx; state.isGuesser = gId === playerId;
        const nonG = room.players.filter(p => p.id !== gId);
        if (!state.isGuesser) { state.clueCount = Object.keys(room.clues||{}).length; state.totalClueExpected = nonG.length; }
        if (room.phase === 'pick_number') state.wordCard = state.isGuesser ? null : (room.wordCard||[]);
        if (room.phase === 'show_word') { state.word = state.isGuesser ? null : room.currentWord; state.chosenNumber = room.chosenNumber||null; state.readyCount = (room.readyPlayers||[]).length; state.isReady = (room.readyPlayers||[]).includes(playerId); }
        if (room.phase === 'writing') { state.word = state.isGuesser ? null : room.currentWord; state.myClue = (room.clues||{})[playerId]||null; }
        if (room.phase === 'review') {
            if (!state.isGuesser) { state.word = room.currentWord; state.allClues = Object.entries(room.clues||{}).map(([pid,clue])=>({playerName:(room.players.find(p=>p.id===pid)||{}).name||'?',clue,removed:(room.removedClues||[]).includes(pid)})); }
            else { state.word = null; state.allClues = []; }
        }
        if (room.phase === 'guessing') {
            state.visibleClues = Object.entries(room.clues||{}).filter(([pid])=>!(room.removedClues||[]).includes(pid)).map(([pid,clue])=>({playerName:(room.players.find(p=>p.id===pid)||{}).name||'?',clue}));
            if (!state.isGuesser) { state.word = room.currentWord; state.allClues = Object.entries(room.clues||{}).map(([pid,clue])=>({playerName:(room.players.find(p=>p.id===pid)||{}).name||'?',clue,removed:(room.removedClues||[]).includes(pid)})); }
        }
        if (['result','gameover'].includes(room.phase)) {
            state.word = room.currentWord; state.guess = room.guess; state.guessCorrect = room.guessCorrect;
            state.allClues = Object.entries(room.clues||{}).map(([pid,clue])=>({playerName:(room.players.find(p=>p.id===pid)||{}).name||'?',clue,removed:(room.removedClues||[]).includes(pid)}));
        }
    }
    if (room.game === 'impostor') {
        const iIdx = room.impostorIndex >= 0 ? room.impostorIndex : -1;
        const iId = (room.players[iIdx]||{}).id || '';
        state.isImpostor = iId === playerId;
        const diff = room.difficulty || 'medium';
        state.category = (state.isImpostor && diff !== 'easy') ? null : (room.category||null);
        state.readyCount = (room.readyPlayers||[]).length;
        state.voteCount = Object.keys(room.votes||{}).length;
        state.myVote = (room.votes||{})[playerId]||null;
        state.impostorIndex = ['result','gameover','impostor_guess'].includes(room.phase) ? iIdx : -1;
        if (['show_role','discussion','voting'].includes(room.phase)) state.word = state.isImpostor ? null : room.currentWord;
        if (room.phase === 'impostor_guess') { state.word = state.isImpostor ? null : room.currentWord; state.impostorCaught = room.impostorCaught||false; state.guess = room.guess; state.guessCorrect = room.guessCorrect; state.votedOut = room.votedOut||null; }
        if (['result','gameover'].includes(room.phase)) { state.word = room.currentWord; state.impostorCaught = room.impostorCaught||false; state.guess = room.guess; state.guessCorrect = room.guessCorrect; state.votedOut = room.votedOut||null; }
    }
    return state;
}

// ==================== CLEANUP ====================

function cleanupRooms() {
    try {
        const files = fs.readdirSync(roomsDir).filter(f => f.endsWith('.json') && !f.startsWith('used_words'));
        const cutoff = Date.now() - 7200000;
        for (const f of files) { try { if (fs.statSync(path.join(roomsDir,f)).mtimeMs < cutoff) fs.unlinkSync(path.join(roomsDir,f)); } catch{} }
    } catch {}
}

// ==================== API ROUTE ====================

function handleApi(req, res) {
    res.set({'Content-Type':'application/json; charset=utf-8','Access-Control-Allow-Origin':'*','Cache-Control':'no-store, no-cache, must-revalidate, max-age=0'});
    const body = {...req.query,...req.body};
    const action = body.action||'';
    const playerId = body.player_id||'';
    const roomCode = (body.room_code||'').toUpperCase().trim();
    function respond(d) { res.json(d); }
    function error(m) { respond({ok:false,error:m}); }
    cleanupRooms();

    switch(action) {
    case 'create_room': {
        const game = body.game||'', name = (body.name||'').trim();
        if (!name) return error('Escreve o teu nome!');
        if (!['justone','impostor'].includes(game)) return error('Jogo inválido!');
        const code = generateCode();
        let difficulty = body.difficulty||'medium';
        if (!['easy','medium','hard'].includes(difficulty)) difficulty = 'medium';
        const room = {code,game,host:playerId,difficulty,players:[{id:playerId,name,score:0}],phase:'lobby',round:0,maxRounds:5,removeDuplicates:true,guesserIndex:-1,currentWord:null,clues:{},removedClues:[],guess:null,guessCorrect:null,category:null,impostorIndex:-1,readyPlayers:[],votes:{},impostorCaught:false,votedOut:null,timerEnd:null};
        saveRoom(room);
        return respond({ok:true,code,state:getSafeState(room,playerId)});
    }
    case 'join_room': {
        const name = (body.name||'').trim();
        if (!name) return error('Escreve o teu nome!');
        if (roomCode.length !== 4) return error('Código deve ter 4 letras!');
        const room = loadRoom(roomCode);
        if (!room) return error('Sala não encontrada!');
        if (room.players.length >= 15) return error('Sala cheia!');
        let found = false;
        for (const p of room.players) { if (p.id === playerId) { p.name = name; found = true; break; } }
        if (!found) {
            if (room.players.some(p => p.name === name)) return error('Já existe alguém com esse nome!');
            room.players.push({id:playerId,name,score:0});
        }
        saveRoom(room);
        return respond({ok:true,code:roomCode,game:room.game,state:getSafeState(room,playerId)});
    }
    case 'get_state': {
        if (!roomCode) return error('Código em falta!');
        const room = loadRoom(roomCode);
        if (!room) return error('Sala não encontrada!');
        let pf = false; const av = body.app_version||null;
        for (const p of room.players) { if (p.id === playerId) { pf = true; if (av && p.version !== av) { p.version = av; saveRoom(room); } break; } }
        if (!pf) return error('Não estás nesta sala!');
        const now = Date.now();
        if (room.timerEnd && now > room.timerEnd) {
            let changed = false;
            if (room.game==='justone' && room.phase==='writing') { justoneCheckDuplicates(room); room.phase='guessing'; room.timerEnd=Date.now()+90000; changed=true; }
            if (room.game==='justone' && room.phase==='guessing') { room.guess='(tempo esgotado)'; room.guessCorrect=false; room.phase='result'; room.timerEnd=null; changed=true; }
            if (room.game==='justone' && room.phase==='result' && room.guessCorrect) { if (room.round>=room.maxRounds) room.phase='gameover'; else justoneNewRound(room); room.timerEnd=null; changed=true; }
            if (room.game==='impostor' && room.phase==='discussion') { room.phase='voting'; room.timerEnd=Date.now()+30000; changed=true; }
            if (changed) saveRoom(room);
        }
        return respond({ok:true,state:getSafeState(room,playerId)});
    }
    case 'set_difficulty': {
        let d = body.difficulty||'medium';
        if (!['easy','medium','hard'].includes(d)) return error('Dificuldade inválida!');
        const room = loadRoom(roomCode); if (!room) return error('Sala não encontrada!');
        if (room.host !== playerId) return error('Só o anfitrião pode mudar!');
        room.difficulty = d; saveRoom(room); return respond({ok:true});
    }
    case 'set_rounds': {
        const r = parseInt(body.rounds)||5;
        if (r<1||r>20) return error('Número de rondas inválido!');
        const room = loadRoom(roomCode); if (!room) return error('Sala não encontrada!');
        if (room.host !== playerId) return error('Só o anfitrião pode mudar!');
        room.maxRounds = r; saveRoom(room); return respond({ok:true});
    }
    case 'set_remove_duplicates': {
        const rm = body.remove_duplicates !== 'false' && body.remove_duplicates !== false;
        const room = loadRoom(roomCode); if (!room) return error('Sala não encontrada!');
        if (room.host !== playerId) return error('Só o anfitrião pode mudar!');
        room.removeDuplicates = rm; saveRoom(room); return respond({ok:true});
    }
    case 'start_game': {
        const room = loadRoom(roomCode); if (!room) return error('Sala não encontrada!');
        if (room.host !== playerId) return error('Só o anfitrião pode começar!');
        if (room.players.length < 3) return error('Precisas de pelo menos 3 jogadores!');
        room.activePlayers = room.players.map(p=>p.id);
        if (room.game==='justone') justoneNewRound(room); else impostorNewRound(room);
        saveRoom(room); return respond({ok:true,state:getSafeState(room,playerId)});
    }
    case 'justone_pick_number': {
        const num = parseInt(body.number)||0;
        if (num<1||num>5) return error('Escolhe um número de 1 a 5!');
        const room = loadRoom(roomCode); if (!room||room.phase!=='pick_number') return error('Ação inválida!');
        const gId = (room.players[room.guesserIndex]||{}).id||'';
        if (playerId !== gId) return error('Só o adivinhador pode escolher!');
        room.chosenNumber = num; room.currentWord = room.wordCard[num-1]; room.phase = 'show_word';
        saveRoom(room); return respond({ok:true});
    }
    case 'justone_ready': {
        const room = loadRoom(roomCode); if (!room||room.phase!=='show_word') return error('Ação inválida!');
        const gId = (room.players[room.guesserIndex]||{}).id||'';
        if (playerId !== gId && !room.readyPlayers.includes(playerId)) room.readyPlayers.push(playerId);
        const nonG = room.players.filter(p=>p.id!==gId);
        if (room.readyPlayers.length >= nonG.length) { room.phase='writing'; room.timerEnd=Date.now()+60000; room.readyPlayers=[]; }
        saveRoom(room); return respond({ok:true});
    }
    case 'justone_clue': {
        const clue = (body.clue||'').trim();
        if (!clue) return error('Escreve uma pista!');
        if (clue.includes(' ')) return error('Apenas uma palavra!');
        const room = loadRoom(roomCode); if (!room||room.phase!=='writing') return error('Ação inválida!');
        const gId = (room.players[room.guesserIndex]||{}).id||'';
        if (playerId === gId) return error('Tu és o adivinhador!');
        if (isDerivative(clue,room.currentWord)) return error('Não podes usar a palavra secreta nem derivados dela!');
        room.clues[playerId] = clue;
        const nonG = room.players.filter(p=>p.id!==gId);
        if (Object.keys(room.clues).length >= nonG.length) { justoneCheckDuplicates(room); room.phase='guessing'; room.timerEnd=Date.now()+90000; }
        saveRoom(room); return respond({ok:true});
    }
    case 'justone_confirm_review': {
        const room = loadRoom(roomCode); if (!room||room.host!==playerId||room.phase!=='review') return error('Ação inválida!');
        room.phase='guessing'; room.timerEnd=Date.now()+90000; saveRoom(room); return respond({ok:true});
    }
    case 'justone_guess': {
        const guess = (body.guess||'').trim();
        if (!guess) return error('Escreve a tua resposta!');
        const room = loadRoom(roomCode); if (!room||room.phase!=='guessing') return error('Ação inválida!');
        const gId = (room.players[room.guesserIndex]||{}).id||'';
        if (playerId !== gId) return error('Não és o adivinhador!');
        room.guess = guess; room.guessCorrect = matchesWord(guess,room.currentWord);
        if (room.guessCorrect) { for (const p of room.players) { if (p.id===gId) p.score+=1; else if (!room.removedClues.includes(p.id)) p.score+=1; } }
        room.phase='result'; room.timerEnd = room.guessCorrect ? Date.now()+5000 : null;
        saveRoom(room); return respond({ok:true});
    }
    case 'justone_skip': {
        const room = loadRoom(roomCode); if (!room||room.phase!=='guessing') return error('Ação inválida!');
        room.guess='(passou)'; room.guessCorrect=false; room.phase='result'; room.timerEnd=null;
        saveRoom(room); return respond({ok:true});
    }
    case 'next_round': {
        const room = loadRoom(roomCode); if (!room||room.host!==playerId) return error('Ação inválida!');
        if (room.round>=room.maxRounds) room.phase='gameover';
        else { if (room.game==='justone') justoneNewRound(room); else impostorNewRound(room); }
        saveRoom(room); return respond({ok:true});
    }
    case 'impostor_ready': {
        const room = loadRoom(roomCode); if (!room||room.phase!=='show_role') return error('Ação inválida!');
        if (!room.readyPlayers.includes(playerId)) room.readyPlayers.push(playerId);
        if (room.readyPlayers.length >= room.players.length) { room.phase='discussion'; room.timerEnd=Date.now()+120000; room.readyPlayers=[]; }
        saveRoom(room); return respond({ok:true});
    }
    case 'impostor_start_vote': {
        const room = loadRoom(roomCode); if (!room||room.host!==playerId||room.phase!=='discussion') return error('Ação inválida!');
        room.phase='voting'; room.timerEnd=Date.now()+30000; saveRoom(room); return respond({ok:true});
    }
    case 'impostor_vote': {
        const vf = body.voted_for||'';
        const room = loadRoom(roomCode); if (!room||room.phase!=='voting') return error('Ação inválida!');
        room.votes[playerId] = vf;
        if (Object.keys(room.votes).length >= room.players.length) {
            const vc = {}; for (const v of Object.values(room.votes)) vc[v]=(vc[v]||0)+1;
            const mx = Math.max(...Object.values(vc));
            const mv = Object.keys(vc).filter(k=>vc[k]===mx);
            const iId = (room.players[room.impostorIndex]||{}).id||'';
            room.votedOut = mv.length===1?mv[0]:null; room.impostorCaught = mv.length===1&&mv[0]===iId;
            if (room.impostorCaught) room.phase='impostor_guess';
            else { for (const p of room.players) { if (p.id===iId) p.score+=3; } room.phase='result'; }
            room.timerEnd=null;
        }
        saveRoom(room); return respond({ok:true});
    }
    case 'impostor_final_guess': {
        const guess = (body.guess||'').trim();
        const room = loadRoom(roomCode); if (!room||room.phase!=='impostor_guess') return error('Ação inválida!');
        const iId = (room.players[room.impostorIndex]||{}).id||'';
        if (playerId !== iId) return error('Não és o impostor!');
        room.guess=guess; room.guessCorrect=matchesWord(guess,room.currentWord);
        if (room.guessCorrect) { for (const p of room.players) { if (p.id===iId) p.score+=2; } }
        else { for (const p of room.players) { if (p.id!==iId) p.score+=2; } }
        room.phase='result'; room.timerEnd=null; saveRoom(room); return respond({ok:true});
    }
    case 'leave_room': {
        const room = loadRoom(roomCode); if (!room) return error('Sala não encontrada!');
        const gIdx = room.guesserIndex>=0?room.guesserIndex:-1;
        const oldGId = (room.players[gIdx]||{}).id||null;
        room.players = room.players.filter(p=>p.id!==playerId);
        if (room.players.length===0) { try{fs.unlinkSync(path.join(roomsDir,`${roomCode}.json`));}catch{} return respond({ok:true,deleted:true}); }
        if (room.host===playerId) room.host=room.players[0].id;
        if (room.activePlayers) room.activePlayers=room.activePlayers.filter(id=>id!==playerId);
        if (room.readyPlayers) room.readyPlayers=room.readyPlayers.filter(id=>id!==playerId);
        if (room.clues) delete room.clues[playerId];
        if (room.votes) delete room.votes[playerId];
        if (room.phase!=='lobby'&&room.players.length<2) {
            room.phase='lobby';room.round=0;room.guesserIndex=-1;room.timerEnd=null;room.readyPlayers=[];
            room.wordCard=[];room.chosenNumber=null;room.currentWord=null;room.clues={};room.removedClues=[];
            room.guess=null;room.guessCorrect=null;room.impostorIndex=-1;room.category=null;room.votes={};room.activePlayers=[];
        } else if (room.phase!=='lobby'&&room.players.length>=2) {
            if (room.game==='justone') {
                if (oldGId&&oldGId===playerId) { if (room.guesserIndex>=room.players.length) room.guesserIndex=room.players.length-1; room.round--; justoneNewRound(room); }
                else { const ni=room.players.findIndex(p=>p.id===oldGId); if(ni>=0)room.guesserIndex=ni; const nonG=room.players.filter(p=>p.id!==oldGId);
                    if(room.phase==='show_word'&&room.readyPlayers.length>=nonG.length){room.phase='writing';room.timerEnd=Date.now()+60000;room.readyPlayers=[];}
                    if(room.phase==='writing'&&Object.keys(room.clues||{}).length>=nonG.length){justoneCheckDuplicates(room);room.phase='guessing';room.timerEnd=Date.now()+90000;}
                }
            }
            if (room.game==='impostor') {
                if(room.phase==='show_role'&&room.readyPlayers.length>=room.players.length){room.phase='discussion';room.timerEnd=Date.now()+120000;room.readyPlayers=[];}
                if(room.phase==='voting'&&Object.keys(room.votes||{}).length>=room.players.length){
                    const vc={};for(const v of Object.values(room.votes))vc[v]=(vc[v]||0)+1;const mx=Math.max(...Object.values(vc));const mv=Object.keys(vc).filter(k=>vc[k]===mx);
                    const iId=(room.players[room.impostorIndex]||{}).id||'';room.votedOut=mv.length===1?mv[0]:null;room.impostorCaught=mv.length===1&&mv[0]===iId;
                    if(room.impostorCaught)room.phase='impostor_guess';else{for(const p of room.players){if(p.id===iId)p.score+=3;}room.phase='result';}room.timerEnd=null;
                }
                if(room.phase==='impostor_guess'){const iId=(room.players[room.impostorIndex]||{}).id||null;if(!iId){room.guess='(impostor saiu)';room.guessCorrect=false;for(const p of room.players)p.score+=2;room.phase='result';room.timerEnd=null;}}
            }
        }
        saveRoom(room); return respond({ok:true,newHost:room.host});
    }
    case 'new_game': {
        const room = loadRoom(roomCode); if (!room||room.host!==playerId) return error('Ação inválida!');
        room.round=0;room.guesserIndex=-1;for(const p of room.players)p.score=0;room.phase='lobby';room.timerEnd=null;room.readyPlayers=[];
        room.wordCard=[];room.chosenNumber=null;room.currentWord=null;room.clues={};room.removedClues=[];room.guess=null;room.guessCorrect=null;
        room.impostorIndex=-1;room.category=null;room.votes={};saveRoom(room);return respond({ok:true});
    }
    case 'restart_game': {
        const room = loadRoom(roomCode); if (!room||room.host!==playerId) return error('Ação inválida!');
        const nd=body.difficulty; if(nd&&['easy','medium','hard'].includes(nd))room.difficulty=nd;
        room.round=0;room.guesserIndex=-1;for(const p of room.players)p.score=0;room.timerEnd=null;room.readyPlayers=[];
        room.wordCard=[];room.chosenNumber=null;room.currentWord=null;room.clues={};room.removedClues=[];room.guess=null;room.guessCorrect=null;
        room.impostorIndex=-1;room.category=null;room.votes={};
        if(room.game==='justone')justoneNewRound(room);else if(room.game==='impostor')impostorNewRound(room);
        saveRoom(room);return respond({ok:true});
    }
    default: return error('Ação desconhecida: '+action);
    }
}

app.get('/api.php', handleApi);
app.post('/api.php', handleApi);

app.get('/icon.php', (req, res) => {
    const size = [192,512].includes(parseInt(req.query.s)) ? parseInt(req.query.s) : 192;
    const file = path.join(__dirname,'icons',`icon-${size}.png`);
    if (fs.existsSync(file)) { res.set('Cache-Control','public, max-age=604800'); return res.sendFile(file); }
    const svg = path.join(__dirname,'icons',`icon-${size}.svg`);
    if (fs.existsSync(svg)) { res.set('Cache-Control','public, max-age=604800'); return res.sendFile(svg); }
    res.status(404).send('Icon not found');
});

app.use(express.static(__dirname, { extensions: ['html'], index: 'index.html' }));

const PORT = process.env.PORT || 3333;
server.listen(PORT, '0.0.0.0', () => {
    console.log(`\n🎮 Jogos de Festa a correr em:`);
    console.log(`   Local:  http://localhost:${PORT}`);
    console.log(`   Rede:   http://0.0.0.0:${PORT}\n`);
});
