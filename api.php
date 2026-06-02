<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$roomsDir = __DIR__ . '/rooms';
if (!is_dir($roomsDir)) mkdir($roomsDir, 0777, true);

// Versão atual da app - incrementar sempre que se faz alterações
define('CURRENT_APP_VERSION', 'v3.0');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$playerId = $_POST['player_id'] ?? $_GET['player_id'] ?? '';
$roomCode = strtoupper(trim($_POST['room_code'] ?? $_GET['room_code'] ?? ''));

// ==================== WORD LISTS ====================

$JUSTONE_WORDS = [
    'easy' => [
        // Animais
        'Gato','Cão','Cavalo','Leão','Tigre','Urso','Macaco','Elefante','Girafa','Cobra',
        'Tubarão','Golfinho','Borboleta','Pinguim','Coelho','Rato','Vaca','Porco','Galinha','Pato',
        'Ovelha','Cabra','Burro','Papagaio','Coruja','Águia','Pomba','Formiga','Abelha','Mosca',
        'Aranha','Caracol','Tartaruga','Sapo','Crocodilo','Baleia','Polvo','Caranguejo','Foca','Flamingo',
        // Comida e Bebida
        'Pizza','Sopa','Arroz','Massa','Pão','Ovo','Queijo','Leite','Água','Sumo',
        'Gelado','Bolo','Chocolate','Bolachas','Cereais','Iogurte','Manteiga','Mel','Açúcar','Sal',
        'Batata','Cenoura','Tomate','Alface','Cebola','Alho','Milho','Ervilha','Feijão','Cogumelo',
        'Banana','Maçã','Laranja','Morango','Uva','Melancia','Limão','Pera','Ananás','Cereja',
        'Frango','Peixe','Camarão','Sandes','Hambúrguer','Salsicha','Presunto','Fiambre','Rissol','Pipocas',
        // Casa e Objetos
        'Casa','Porta','Janela','Telhado','Escada','Chave','Cama','Mesa','Cadeira','Sofá',
        'Televisão','Telefone','Computador','Frigorífico','Fogão','Forno','Micro-ondas','Torradeira','Chaleira','Panela',
        'Prato','Copo','Garfo','Faca','Colher','Toalha','Sabão','Escova','Espelho','Relógio',
        'Candeeiro','Almofada','Tapete','Cortina','Vassoura','Balde','Tesoura','Cola','Lápis','Borracha',
        // Natureza e Tempo
        'Sol','Lua','Estrela','Nuvem','Chuva','Neve','Vento','Arco-íris','Relâmpago','Trovão',
        'Praia','Montanha','Rio','Lago','Mar','Floresta','Ilha','Deserto','Jardim','Parque',
        'Árvore','Flor','Relva','Folha','Semente','Raiz','Pedra','Areia','Terra','Lama',
        'Fogo','Gelo','Onda','Nevoeiro','Geada','Tempestade','Tornado','Vulcão','Terramoto','Cometa',
        // Corpo e Família
        'Mãe','Pai','Avó','Avô','Irmão','Bebé','Filho','Tio','Prima','Família',
        'Olho','Nariz','Boca','Orelha','Mão','Pé','Cabeça','Barriga','Joelho','Dedo',
        'Coração','Dente','Cabelo','Ombro','Pescoço','Língua','Unha','Cotovelo','Costas','Perna',
        // Escola e Trabalho
        'Escola','Professor','Livro','Caderno','Caneta','Mochila','Quadro','Régua','Mapa','Globo',
        'Médico','Polícia','Bombeiro','Carteiro','Padeiro','Pescador','Jardineiro','Pintor','Condutor','Cozinheiro',
        // Roupa e Acessórios
        'Chapéu','Sapatos','Botas','Vestido','Casaco','Calças','Camisa','Saia','Meias','Luvas',
        'Cachecol','Cinto','Gravata','Pijama','Óculos','Anel','Pulseira','Colar','Brincos','Relógio',
        // Transporte
        'Carro','Avião','Barco','Comboio','Bicicleta','Autocarro','Mota','Táxi','Foguetão','Helicóptero',
        'Navio','Trator','Ambulância','Camião','Metro','Skate','Patins','Canoa','Balão','Submarino',
        // Brinquedos e Jogos
        'Bola','Boneca','Puzzle','Lego','Pião','Balancé','Escorrega','Baloiço','Papagaio','Dado',
        'Cartas','Dominó','Xadrez','Damas','Bicicleta','Patins','Videojogos','Peluche','Robot','Máscara',
        // Festa e Celebração
        'Natal','Aniversário','Festa','Presente','Balão','Vela','Palhaço','Mágico','Circo','Coroa',
        'Rei','Rainha','Príncipe','Princesa','Castelo','Espada','Escudo','Dragão','Pirata','Tesouro',
    ],
    'medium' => [
        // Aventura e Fantasia
        'Astronauta','Diamante','Pirata','Carnaval','Submarino','Dragão','Safari','Labirinto','Samurai','Gladiador',
        'Viking','Ninja','Sereia','Unicórnio','Fantasma','Vampiro','Lobisomem','Múmia','Zombie','Feiticeiro',
        'Cavaleiro','Princesa','Tesouro','Espada','Magia','Feitiço','Poção','Pergaminho','Amuleto','Talismã',
        'Catapulta','Arco','Flecha','Escudo','Armadura','Trono','Coroa','Cetro','Calabouço','Torre',
        // Ciência e Espaço
        'Eclipse','Galáxia','Planeta','Meteorito','Aurora','Constelação','Órbita','Satélite','Asteroide','Cometa',
        'Telescópio','Microscópio','Laboratório','Experiência','Fórmula','Molécula','Átomo','Cristal','Fóssil','Âmbar',
        'Dinossauro','Pterodáctilo','Mamute','Megalodon','Triceratops','Arqueologia','Escavação','Relíquia','Artefacto','Hieróglifo',
        // Cultura e Entretenimento
        'Cinema','Teatro','Ópera','Concerto','Festival','Circo','Musical','Comédia','Drama','Tragédia',
        'Fotografia','Pintura','Escultura','Museu','Galeria','Exposição','Retrato','Paisagem','Mosaico','Vitral',
        'Biblioteca','Universidade','Enciclopédia','Dicionário','Romance','Poesia','Lenda','Fábula','Conto','Mito',
        'Maestro','Bailarina','Acrobata','Malabarista','Trapézio','Marioneta','Ventrílocuo','Ilusionista','Contorcionista','Equilibrista',
        // Desporto e Aventura
        'Surfista','Mergulhador','Alpinista','Parapente','Salto-elástico','Descida','Canoagem','Escalada','Espeleologia','Orientação',
        'Maratona','Triatlo','Pentatlo','Esgrima','Tiro','Equitação','Polo','Rugby','Cricket','Hóquei',
        'Skate','Snowboard','Wakeboard','Windsurf','Kitesurf','Canoagem','Paraquedas','Rappel','Trenó','Tobogã',
        // Comida do Mundo
        'Sushi','Hambúrguer','Croissant','Panqueca','Taco','Burrito','Kebab','Curry','Paella','Risotto',
        'Ramen','Empada','Pretzel','Waffle','Brownie','Cheesecake','Tiramisu','Brioche','Fondue','Churros',
        'Francesinha','Bacalhau','Bifana','Açorda','Cataplana','Alheira','Rojões','Tripas','Chanfana','Migas',
        // Portugal e Cultura
        'Fado','Azulejo','Ginjinha','Tuk-tuk','Elétrico','Sardinha','Bacalhau','Saudade','Alfama','Sintra',
        'Benfica','Porto','Sporting','Ronaldo','Eusébio','Pessoa','Amália','Camões','Pastéis','Fátima',
        // Lugares e Construções
        'Pirâmide','Coliseu','Muralha','Esfinge','Parthenon','Stonehenge','Colosseu','Panteão','Alhambra','Acrópole',
        'Farol','Cascata','Géiser','Oásis','Recife','Glaciar','Fiorde','Canyon','Cratera','Atol',
        // Profissões
        'Detetive','Espião','Arqueólogo','Paleontólogo','Astrónomo','Biólogo','Químico','Físico','Geólogo','Meteorologista',
        'Realizador','Argumentista','Cenógrafo','Figurinista','Coreógrafo','Compositor','Maestro','Escultor','Ilustrador','Designer',
        // Objetos Curiosos
        'Bússola','Bumerangue','Catapulta','Ampulheta','Astrolábio','Caleidoscópio','Binóculos','Periscópio','Sextante','Monóculo',
        'Origami','Ábaco','Papiro','Pendulo','Metrónomo','Compasso','Gramofone','Monociclo','Sismógrafo','Astrolábio',
    ],
    'hard' => [
        // Conceitos Abstratos
        'Saudade','Nostalgia','Ironia','Paradoxo','Metáfora','Utopia','Karma','Epifania','Empatia','Anarquia',
        'Altruísmo','Egoísmo','Nihilismo','Hedonismo','Estoicismo','Pragmatismo','Idealismo','Relativismo','Determinismo','Existencialismo',
        'Ambiguidade','Dualidade','Dicotomia','Sincronicidade','Serendipidade','Obsolescência','Resiliência','Perseverança','Procrastinação','Transcendência',
        'Consciência','Subconsciência','Intuição','Percepção','Cognição','Abstração','Racionalidade','Impulsividade','Compulsão','Catarse',
        // Política e Sociedade
        'Burocracia','Democracia','Diplomacia','Revolução','Propaganda','Censura','Anarquia','Tirania','Oligarquia','Plutocracia',
        'Imperialismo','Colonialismo','Nacionalismo','Populismo','Totalitarismo','Fascismo','Comunismo','Socialismo','Capitalismo','Liberalismo',
        'Gentrificação','Globalização','Sustentabilidade','Segregação','Emancipação','Soberania','Autonomia','Federalismo','Constitucionalismo','Meritocracia',
        // Ciência Avançada
        'Fotossíntese','Gravidade','Evolução','Relatividade','Antimatéria','Quasar','Nebulosa','Supernova','Pulsar','Magnetismo',
        'Entropia','Termodinâmica','Eletromagnetismo','Radioatividade','Fusão','Fissão','Radiação','Espectro','Frequência','Ressonância',
        'ADN','ARN','Genoma','Mutação','Simbiose','Metamorfose','Fototropismo','Homeostase','Osmose','Mitocôndria',
        'Mitose','Meiose','Enzima','Catalisador','Polímero','Isótopo','Neutrino','Protão','Eletrão','Nêutron',
        // Tecnologia
        'Algoritmo','Criptografia','Holograma','Blockchain','Nanotecnologia','Biotecnologia','Robótica','Firewall','Malware','Servidor',
        'Streaming','Metaverso','Criptomoeda','Cibersegurança','Pixel','Binário','Compilador','Depurador','Arquitectura','Encriptação',
        // Arte e Movimentos
        'Surrealismo','Renascimento','Barroco','Impressionismo','Cubismo','Dadaísmo','Expressionismo','Modernismo','Maneirismo','Rococó',
        'Pós-modernismo','Minimalismo','Brutalismo','Romantismo','Naturalismo','Realismo','Simbolismo','Futurismo','Construtivismo','Suprematismo',
        // Psicologia
        'Claustrofobia','Agorafobia','Insónia','Amnésia','Sinestesia','Placebo','Nocebo','Paranoia','Esquizofrenia','Narcisismo',
        'Hipnose','Psicanálise','Projeção','Sublimação','Transferência','Condicionamento','Reforço','Supressão','Dissociação','Racionalização',
        // Filosofia
        'Existência','Essência','Fenomenologia','Hermenêutica','Dialética','Epistemologia','Ontologia','Metafísica','Teleologia','Deontologia',
        'Solipsismo','Silogismo','Sofismo','Dogmatismo','Ceticismo','Relativismo','Absolutismo','Cogito','Superhomem','Zeitgeist',
        // Economia e Finanças
        'Inflação','Deflação','Estagflação','Recessão','Depressão','Especulação','Austeridade','Protecionismo','Mercantilismo','Monopólio',
        'PIB','Câmbio','Derivados','Dividendo','Hipoteca','Amortização','Startup','Unicórnio','Cotação','Diversificação',
        // Fenómenos e Natureza
        'Maremoto','Monção','Permafrost','Estratosfera','Tectónica','Erosão','Sedimentação','Sublimação','Condensação','Evaporação',
        'Bioluminescência','Magnetosfera','Ionosfera','Estratosfera','Troposfera','Desertificação','Eutrofização','Acidificação','Desflorestação','Fotólise',
        // Linguística e Literatura
        'Onomatopeia','Aliteração','Sinédoque','Metonímia','Hipérbole','Eufemismo','Oximoro','Pleonasmo','Anacoluto','Elipse',
        'Solilóquio','Epílogo','Prólogo','Antítese','Alegoria','Sátira','Paródia','Pastiche','Intertextualidade','Metalinguagem',
    ],
];

$IMPOSTOR_CATEGORIES = [
    'easy' => [
        ['categoria'=>'Animais de Estimação','palavras'=>['Gato','Cão','Hamster','Coelho','Peixe','Tartaruga','Papagaio','Canário','Periquito','Porquinho-da-índia','Gerbil','Furão','Chinchila','Iguana','Gecko']],
        ['categoria'=>'Animais Selvagens','palavras'=>['Leão','Tigre','Elefante','Girafa','Zebra','Hipopótamo','Rinoceronte','Gorila','Chimpanzé','Urso','Lobo','Raposa','Veado','Crocodilo','Cobra']],
        ['categoria'=>'Animais do Mar','palavras'=>['Golfinho','Baleia','Tubarão','Polvo','Lula','Enguia','Salmão','Caranguejo','Lagosta','Medusa','Foca','Morsa','Pinguim','Raia','Atum']],
        ['categoria'=>'Insetos e Bichos','palavras'=>['Borboleta','Abelha','Formiga','Joaninha','Aranha','Mosquito','Mosca','Grilo','Gafanhoto','Libelinha','Caracol','Lesma','Minhoca','Escaravelho','Centopeia']],
        ['categoria'=>'Comida','palavras'=>['Pizza','Hambúrguer','Massa','Arroz','Sopa','Salada','Frango','Peixe','Bife','Sandes','Omelete','Estufado','Gratinado','Empadão','Croquetes']],
        ['categoria'=>'Doces e Sobremesas','palavras'=>['Gelado','Bolo','Chocolate','Bolachas','Gomas','Rebuçados','Pudim','Mousse','Gelatina','Donuts','Panquecas','Waffles','Crepes','Tarte','Cupcake']],
        ['categoria'=>'Fruta','palavras'=>['Maçã','Banana','Laranja','Morango','Uva','Melancia','Ananás','Manga','Kiwi','Pêssego','Cereja','Limão','Pera','Coco','Framboesa']],
        ['categoria'=>'Legumes','palavras'=>['Batata','Cenoura','Tomate','Alface','Cebola','Alho','Brócolos','Couve-flor','Pepino','Pimento','Abóbora','Beringela','Espinafre','Nabo','Ervilha']],
        ['categoria'=>'Bebidas','palavras'=>['Água','Sumo','Leite','Coca-Cola','Limonada','Chá','Café','Batido','Cerveja','Ginjinha','Guaraná','Fanta','Sprite','Nestea','Compal']],
        ['categoria'=>'Cores','palavras'=>['Vermelho','Azul','Verde','Amarelo','Laranja','Rosa','Roxo','Preto','Branco','Castanho','Dourado','Prateado','Cinzento','Turquesa','Violeta']],
        ['categoria'=>'Corpo Humano','palavras'=>['Olho','Nariz','Boca','Orelha','Mão','Pé','Cabeça','Barriga','Joelho','Dedo','Coração','Língua','Dente','Cabelo','Ombro']],
        ['categoria'=>'Roupa','palavras'=>['Calças','T-shirt','Vestido','Casaco','Sapatos','Chapéu','Cachecol','Luvas','Meias','Gravata','Botas','Cinto','Saia','Camisa','Pijama']],
        ['categoria'=>'Transportes','palavras'=>['Carro','Avião','Barco','Comboio','Bicicleta','Autocarro','Mota','Táxi','Helicóptero','Foguetão','Trator','Ambulância','Camião','Metro','Skate']],
        ['categoria'=>'Desporto','palavras'=>['Futebol','Basquetebol','Ténis','Natação','Surf','Ciclismo','Atletismo','Voleibol','Ginástica','Karaté','Patinagem','Boxe','Golfe','Ski','Andebol']],
        ['categoria'=>'Objetos de Casa','palavras'=>['Televisão','Sofá','Frigorífico','Cama','Mesa','Cadeira','Espelho','Relógio','Candeeiro','Almofada','Tapete','Cortina','Fogão','Banheira','Vassoura']],
        ['categoria'=>'Material Escolar','palavras'=>['Lápis','Caneta','Borracha','Régua','Caderno','Mochila','Afia','Compasso','Esquadro','Marcador','Cola','Tesoura','Furador','Agrafador','Calculadora']],
        ['categoria'=>'Tempo e Clima','palavras'=>['Sol','Chuva','Neve','Vento','Nuvem','Trovoada','Arco-íris','Granizo','Nevoeiro','Gelo','Tempestade','Tornado','Seca','Geada','Brisa']],
        ['categoria'=>'Família','palavras'=>['Mãe','Pai','Avó','Avô','Irmão','Irmã','Tio','Tia','Primo','Prima','Filho','Filha','Sobrinho','Madrinha','Padrinho']],
        ['categoria'=>'Divisões da Casa','palavras'=>['Quarto','Sala','Cozinha','WC','Garagem','Jardim','Varanda','Sótão','Cave','Escritório','Corredor','Despensa','Lavandaria','Entrada','Terraço']],
        ['categoria'=>'Brinquedos','palavras'=>['Boneca','Lego','Puzzle','Peluche','Bola','Carrinhos','Pião','Yo-Yo','Consola','Patins','Trotinete','Bumerangue','Caleidoscópio','Slime','Nerf']],
    ],
    'medium' => [
        ['categoria'=>'Países da Europa','palavras'=>['Portugal','Espanha','França','Itália','Alemanha','Inglaterra','Holanda','Bélgica','Suíça','Áustria','Grécia','Suécia','Noruega','Irlanda','Polónia']],
        ['categoria'=>'Países do Mundo','palavras'=>['Brasil','Japão','Austrália','Egito','México','Canadá','China','Índia','Argentina','Rússia','Coreia','Turquia','Marrocos','Tailândia','Colômbia']],
        ['categoria'=>'Capitais','palavras'=>['Lisboa','Madrid','Paris','Roma','Berlim','Londres','Amesterdão','Bruxelas','Viena','Atenas','Tóquio','Washington','Pequim','Moscovo','Cairo']],
        ['categoria'=>'Cidades Portuguesas','palavras'=>['Lisboa','Porto','Braga','Coimbra','Faro','Aveiro','Évora','Guimarães','Setúbal','Viseu','Leiria','Funchal','Sintra','Cascais','Bragança']],
        ['categoria'=>'Profissões','palavras'=>['Médico','Professor','Bombeiro','Polícia','Cozinheiro','Piloto','Astronauta','Veterinário','Arquiteto','Cantor','Cientista','Jornalista','Fotógrafo','Mecânico','Advogado']],
        ['categoria'=>'Profissões Criativas','palavras'=>['Ator','Realizador','Músico','Pintor','Escultor','Escritor','Designer','Ilustrador','Fotógrafo','Coreógrafo','Estilista','Tatuador','DJ','Youtuber','Influencer']],
        ['categoria'=>'Filmes Clássicos','palavras'=>['Titanic','Matrix','Avatar','Rocky','Gladiador','Inception','Interstellar','Jaws','Alien','Rambo','Grease','Scarface','Psycho','Bambi','Dumbo']],
        ['categoria'=>'Filmes de Animação','palavras'=>['Shrek','Frozen','Nemo','Ratatouille','Up','Coco','Moana','Wall-E','Madagáscar','Monstros','Zootopia','Encanto','Luca','Tarzan','Bolt']],
        ['categoria'=>'Séries','palavras'=>['Friends','Narcos','Dark','Vikings','Chernobyl','Lost','Dexter','Suits','Fargo','Seinfeld','House','Hannibal','Sherlock','Lucifer','Homeland']],
        ['categoria'=>'Instrumentos','palavras'=>['Guitarra','Piano','Bateria','Violino','Flauta','Saxofone','Trompete','Harpa','Ukulele','Acordeão','Baixo','Gaita','Banjo','Xilofone','Contrabaixo']],
        ['categoria'=>'Géneros Musicais','palavras'=>['Rock','Pop','Jazz','Reggae','Samba','Fado','Country','Metal','Eletrónica','Blues','Clássica','R&B','Punk','Gospel','Funk']],
        ['categoria'=>'Super-heróis','palavras'=>['Spider-Man','Batman','Superman','Hulk','Thor','Deadpool','Wolverine','Flash','Aquaman','Robin','Elektra','Gambit','Ciclope','Tempestade','Daredevil']],
        ['categoria'=>'Vilões','palavras'=>['Joker','Thanos','Voldemort','Magneto','Loki','Ultron','Venom','Pennywise','Hannibal','Sauron','Cruella','Malévola','Jafar','Megatron','Mysterio']],
        ['categoria'=>'Jogos de Tabuleiro','palavras'=>['Xadrez','Damas','Monopólio','Uno','Poker','Dominó','Scrabble','Jenga','Ludo','Sueca','Bingo','Risk','Cluedo','Pictionary','Battleship']],
        ['categoria'=>'Videojogos','palavras'=>['Minecraft','Fortnite','Mario','Zelda','FIFA','GTA','Tetris','Pac-Man','Pokémon','Sonic','Roblox','Valorant','Overwatch','Halo','Skyrim']],
        ['categoria'=>'Monumentos','palavras'=>['Coliseu','Pirâmides','Stonehenge','Acrópole','Alhambra','Panteão','Versalhes','Kremlin','Esfinge','Parthenon','Vaticano','Angkor','Petra','Chichén','Giralda']],
        ['categoria'=>'Espaço','palavras'=>['Sol','Lua','Marte','Júpiter','Saturno','Vénus','Mercúrio','Neptuno','Urano','Plutão','Estrela','Cometa','Galáxia','Asteroide','Nebulosa']],
        ['categoria'=>'Marcas Famosas','palavras'=>['Nike','Adidas','Apple','Samsung','Coca-Cola','McDonald\'s','Google','Amazon','Netflix','Disney','IKEA','Zara','Toyota','Lego','Ferrari']],
        ['categoria'=>'Doces Portugueses','palavras'=>['Queijada','Travesseiro','Serradura','Rabanada','Fartura','Sonho','Bolo-rei','Arrufada','Trouxas','Cavacas','Tigelada','Pampilho','Jesuíta','Tentúgal','Barriga-de-freira']],
        ['categoria'=>'Comida Portuguesa','palavras'=>['Francesinha','Bifana','Feijoada','Cataplana','Alheira','Açorda','Chanfana','Migas','Rojões','Tripas','Cozido','Caldeirada','Bacalhau','Sardinha','Leitão']],
    ],
    'hard' => [
        ['categoria'=>'Conceitos Abstratos','palavras'=>['Liberdade','Justiça','Coragem','Sabedoria','Esperança','Destino','Verdade','Silêncio','Saudade','Ironia','Paradoxo','Utopia','Karma','Empatia','Altruísmo']],
        ['categoria'=>'Sentimentos Complexos','palavras'=>['Nostalgia','Melancolia','Euforia','Resignação','Perplexidade','Ambivalência','Serenidade','Angústia','Êxtase','Apatia','Remorso','Compaixão','Indignação','Gratidão','Vulnerabilidade']],
        ['categoria'=>'Ciência','palavras'=>['Gravidade','Átomo','Molécula','Evolução','Fotossíntese','Célula','ADN','Eletricidade','Magnetismo','Energia','Oxigénio','Enzima','Neurónio','Mitose','Osmose']],
        ['categoria'=>'Ciência Avançada','palavras'=>['Antimatéria','Quasar','Supernova','Entropia','Termodinâmica','Radioatividade','Fusão','Fissão','Neutrino','Radiação','Espectro','Isótopo','Catalisador','Magnetismo','Ressonância']],
        ['categoria'=>'História','palavras'=>['Revolução','Império','Renascimento','Cruzadas','Feudalismo','Monarquia','República','Ditadura','Inquisição','Descobrimentos','Iluminismo','Colonialismo','Independência','Absolutismo','Reconquista']],
        ['categoria'=>'Figuras Históricas','palavras'=>['Cleópatra','Napoleão','Galileu','Shakespeare','Einstein','Newton','Darwin','Colombo','Mozart','Beethoven','Hipócrates','Pitágoras','Aristóteles','Platão','Sócrates']],
        ['categoria'=>'Movimentos Artísticos','palavras'=>['Impressionismo','Surrealismo','Cubismo','Barroco','Gótico','Renascimento','Expressionismo','Minimalismo','Romantismo','Realismo','Futurismo','Dadaísmo','Construtivismo','Rococó','Maneirismo']],
        ['categoria'=>'Psicologia','palavras'=>['Empatia','Ansiedade','Intuição','Consciência','Instinto','Ego','Fobia','Trauma','Obsessão','Ilusão','Motivação','Personalidade','Projeção','Sublimação','Catarse']],
        ['categoria'=>'Tecnologia','palavras'=>['Algoritmo','Criptografia','Blockchain','Holograma','Firewall','Cloud','Malware','Streaming','Servidor','Metaverso','Nanotecnologia','Biotecnologia','Robótica','Encriptação','Compilador']],
        ['categoria'=>'Mitologia Grega','palavras'=>['Zeus','Poseidon','Hades','Atena','Apolo','Afrodite','Ares','Hermes','Dioniso','Hefesto','Minotauro','Medusa','Cíclope','Centauro','Fénix']],
        ['categoria'=>'Mitologia Nórdica','palavras'=>['Odin','Thor','Loki','Freya','Fenrir','Jörmungandr','Ragnarök','Valhalla','Yggdrasil','Bifrost','Asgard','Midgard','Valquíria','Mjolnir','Runa']],
        ['categoria'=>'Filosofia','palavras'=>['Existência','Moral','Ética','Lógica','Razão','Percepção','Essência','Dualismo','Niilismo','Estoicismo','Hedonismo','Determinismo','Empirismo','Fenomenologia','Dialética']],
        ['categoria'=>'Economia','palavras'=>['Inflação','Recessão','Especulação','Monopólio','Capitalismo','Socialismo','PIB','Austeridade','Protecionismo','Globalização','Privatização','Startup','Dividendo','Hipoteca','Amortização']],
        ['categoria'=>'Fenómenos Naturais','palavras'=>['Bioluminescência','Maremoto','Monção','Tectónica','Erosão','Permafrost','Magnetosfera','Sublimação','Condensação','Sismologia','Vulcanismo','Tsunami','Avalanche','Geotermia','Fotólise']],
        ['categoria'=>'Literatura','palavras'=>['Metáfora','Alegoria','Sátira','Tragédia','Epopeia','Solilóquio','Distopia','Naturalismo','Simbolismo','Narrativa','Prosa','Verso','Soneto','Haiku','Fábula']],
        ['categoria'=>'Palavras Portuguesas','palavras'=>['Saudade','Desenrascanço','Madrugada','Lusitano','Sebastianismo','Minhoto','Alentejano','Açoriano','Madeirense','Beirão','Algarvio','Fadista','Tascas','Azulejo','Bacalhau']],
        ['categoria'=>'Medicina','palavras'=>['Diagnóstico','Prognóstico','Sintoma','Síndrome','Imunidade','Anticorpo','Vacina','Anestesia','Biopsia','Metástase','Remissão','Terapia','Reabilitação','Cirurgia','Farmacologia']],
        ['categoria'=>'Direito','palavras'=>['Constituição','Legislação','Jurisprudência','Amnistia','Prescrição','Mandato','Sentença','Recurso','Acusação','Defesa','Júri','Veredito','Absolvição','Precedente','Tribunal']],
        ['categoria'=>'Música Clássica','palavras'=>['Sinfonia','Concerto','Sonata','Ópera','Requiem','Fuga','Prelúdio','Nocturno','Allegro','Adagio','Forte','Piano','Crescendo','Staccato','Vibrato']],
        ['categoria'=>'Astronomia','palavras'=>['Constelação','Nebulosa','Pulsar','Quasar','Exoplaneta','Magnetar','Protostar','Paralaxe','Redshift','Singularidade','Supernova','Eclipse','Asteroide','Telescópio','Cosmologia']],
    ],
];

// ==================== HELPERS ====================

$_lockFp = null; // File handle mantido durante operações atómicas

function loadRoom($code) {
    global $roomsDir;
    $file = "$roomsDir/$code.json";
    clearstatcache(true, $file);
    if (!file_exists($file)) return null;
    // Usar fopen + LOCK_SH (obrigatório no Windows para não conflitar com LOCK_EX)
    $fp = fopen($file, 'r');
    if (!$fp) return null;
    flock($fp, LOCK_SH); // Espera que LOCK_EX liberte
    fseek($fp, 0);
    $contents = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    if (!$contents) return null;
    return json_decode($contents, true);
}

// Carrega sala com lock EXCLUSIVO - usar quando vais modificar e guardar
function loadRoomLocked($code) {
    global $roomsDir, $_lockFp;
    $file = "$roomsDir/$code.json";
    clearstatcache(true, $file);
    if (!file_exists($file)) return null;
    $_lockFp = fopen($file, 'c+');
    if (!$_lockFp) return null;
    flock($_lockFp, LOCK_EX);
    fseek($_lockFp, 0);
    $contents = stream_get_contents($_lockFp);
    if (!$contents) { flock($_lockFp, LOCK_UN); fclose($_lockFp); $_lockFp = null; return null; }
    return json_decode($contents, true);
}

function saveRoom($room) {
    global $roomsDir, $_lockFp;
    // Se temos lock exclusivo, usar o mesmo file handle
    if ($_lockFp) {
        fseek($_lockFp, 0);
        ftruncate($_lockFp, 0);
        fwrite($_lockFp, json_encode($room, JSON_UNESCAPED_UNICODE));
        fflush($_lockFp);
        flock($_lockFp, LOCK_UN);
        fclose($_lockFp);
        $_lockFp = null;
        return;
    }
    // Fallback: abrir novo
    $file = "$roomsDir/{$room['code']}.json";
    $fp = fopen($file, 'c');
    if (!$fp) return;
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($room, JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

// Liberar lock se saveRoom não for chamado
function unlockRoom() {
    global $_lockFp;
    if ($_lockFp) { flock($_lockFp, LOCK_UN); fclose($_lockFp); $_lockFp = null; }
}

function generateCode() {
    global $roomsDir;
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 4; $i++) $code .= $chars[random_int(0, strlen($chars)-1)];
    } while (file_exists("$roomsDir/$code.json"));
    return $code;
}

function normalize($s) {
    $s = mb_strtolower(trim($s), 'UTF-8');
    // Remove acentos - compatível com qualquer PHP
    $map = [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
        'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
        'ç'=>'c','ñ'=>'n',
    ];
    $s = strtr($s, $map);
    return $s;
}

// Obter raiz da palavra (stemming básico português)
function getStem($word) {
    $s = normalize($word);
    if (strlen($s) <= 3) return $s;
    // Sufixos portugueses comuns (do mais longo para o mais curto)
    $suffixes = [
        'mente', 'ções', 'ação', 'ismo', 'ista', 'ável', 'ível',
        'ando', 'endo', 'indo', 'ados', 'idos', 'adas', 'idas',
        'ores', 'eira', 'eiro', 'inha', 'inho', 'ões', 'ais',
        'eis', 'ado', 'ido', 'ada', 'ida', 'oso', 'osa',
        'dor', 'tor', 'ção', 'zes',
        'es', 'ns', 'is', 'os', 'as', 'ar', 'er', 'ir',
        's',
    ];
    foreach ($suffixes as $suf) {
        $len = strlen($suf);
        if (strlen($s) > $len + 2 && substr($s, -$len) === $suf) {
            return substr($s, 0, -$len);
        }
    }
    return $s;
}

// Verificar se o palpite corresponde à palavra (aceita singular/plural, acentos)
function matchesWord($guess, $word) {
    $ng = normalize($guess);
    $nw = normalize($word);
    // Comparação direta
    if ($ng === $nw) return true;
    // Um é o outro + 's' (plural simples)
    if ($ng . 's' === $nw || $nw . 's' === $ng) return true;
    // Um é o outro + 'es' (plural -es)
    if ($ng . 'es' === $nw || $nw . 'es' === $ng) return true;
    // Plural ão → ões (ex: coração → corações)
    if (substr($nw, -2) === 'ao' && $ng === substr($nw, 0, -2) . 'oes') return true;
    if (substr($ng, -2) === 'ao' && $nw === substr($ng, 0, -2) . 'oes') return true;
    // Plural ão → ães (ex: pão → pães)
    if (substr($nw, -2) === 'ao' && $ng === substr($nw, 0, -2) . 'aes') return true;
    if (substr($ng, -2) === 'ao' && $nw === substr($ng, 0, -2) . 'aes') return true;
    // Plural al → ais (ex: animal → animais)
    if (substr($nw, -2) === 'al' && $ng === substr($nw, 0, -1) . 'is') return true;
    if (substr($ng, -2) === 'al' && $nw === substr($ng, 0, -1) . 'is') return true;
    // Plural el → eis (ex: papel → papéis)
    if (substr($nw, -2) === 'el' && $ng === substr($nw, 0, -1) . 'is') return true;
    if (substr($ng, -2) === 'el' && $nw === substr($ng, 0, -1) . 'is') return true;
    // Plural il → is (ex: fóssil → fósseis/fosseis)
    if (substr($nw, -2) === 'il' && $ng === substr($nw, 0, -2) . 'is') return true;
    if (substr($ng, -2) === 'il' && $nw === substr($ng, 0, -2) . 'is') return true;
    // Mesma raiz (stem matching)
    if (strlen($ng) >= 3 && strlen($nw) >= 3 && getStem($guess) === getStem($word)) return true;
    return false;
}

// Verificar se a pista é derivada da palavra secreta
function isDerivative($clue, $word) {
    $nc = normalize($clue);
    $nw = normalize($word);
    if (strlen($nc) < 3 || strlen($nw) < 3) return false;
    // Pista é a própria palavra
    if ($nc === $nw) return true;
    // Uma contém a outra
    if (strpos($nc, $nw) !== false || strpos($nw, $nc) !== false) return true;
    // Mesma raiz (stem)
    $sc = getStem($clue);
    $sw = getStem($word);
    if (strlen($sc) >= 3 && strlen($sw) >= 3 && $sc === $sw) return true;
    return false;
}

function respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function error($msg) {
    unlockRoom(); // Libertar lock se existir antes de sair
    respond(['ok' => false, 'error' => $msg]);
}

function getSafeState($room, $playerId) {
    $active = $room['activePlayers'] ?? [];
    $isSpectator = $room['phase'] !== 'lobby' && !empty($active) && !in_array($playerId, $active);
    $state = [
        'code' => $room['code'],
        'game' => $room['game'],
        'host' => $room['host'],
        'difficulty' => $room['difficulty'] ?? 'medium',
        'removeDuplicates' => $room['removeDuplicates'] ?? true,
        'phase' => $isSpectator ? 'spectator' : $room['phase'],
        'round' => $room['round'],
        'maxRounds' => $room['maxRounds'],
        'myId' => $playerId,
        'isHost' => $playerId === $room['host'],
        'serverVersion' => CURRENT_APP_VERSION,
        'timerEnd' => $room['timerEnd'] ?? null,
        'players' => [],
    ];

    foreach ($room['players'] as $p) {
        $state['players'][] = [
            'id' => $p['id'],
            'name' => $p['name'],
            'score' => $p['score'],
            'isHost' => $p['id'] === $room['host'],
            'version' => $p['version'] ?? null,
        ];
    }

    if ($room['game'] === 'justone') {
        $guesserIdx = $room['guesserIndex'] ?? -1;
        $guesserId = $room['players'][$guesserIdx]['id'] ?? '';
        $state['guesserIndex'] = $guesserIdx;
        $state['isGuesser'] = $guesserId === $playerId;

        $nonGuessers = array_filter($room['players'], function($p) use ($guesserId) { return $p['id'] !== $guesserId; });
        // O adivinhador NÃO pode saber quantas pistas foram enviadas
        if (!$state['isGuesser']) {
            $state['clueCount'] = count($room['clues'] ?? []);
            $state['totalClueExpected'] = count($nonGuessers);
        }

        if ($room['phase'] === 'pick_number') {
            // O adivinhador vê 5 números para escolher (NÃO vê as palavras)
            // Os outros veem as 5 palavras e esperam
            $state['wordCard'] = $state['isGuesser'] ? null : ($room['wordCard'] ?? []);
        }
        if ($room['phase'] === 'show_word') {
            $state['word'] = $state['isGuesser'] ? null : $room['currentWord'];
            $state['chosenNumber'] = $room['chosenNumber'] ?? null;
            $state['readyCount'] = count($room['readyPlayers'] ?? []);
            $state['isReady'] = in_array($playerId, $room['readyPlayers'] ?? []);
        }
        if ($room['phase'] === 'writing') {
            $state['word'] = $state['isGuesser'] ? null : $room['currentWord'];
            $state['myClue'] = $room['clues'][$playerId] ?? null;
        }
        if ($room['phase'] === 'review') {
            // O adivinhador NÃO pode ver a palavra nem as pistas na revisão
            if (!$state['isGuesser']) {
                $state['word'] = $room['currentWord'];
                $state['allClues'] = [];
                foreach (($room['clues'] ?? []) as $pid => $clue) {
                    $pname = '?';
                    foreach ($room['players'] as $p) { if ($p['id'] === $pid) $pname = $p['name']; }
                    $state['allClues'][] = [
                        'playerName' => $pname,
                        'clue' => $clue,
                        'removed' => in_array($pid, $room['removedClues'] ?? []),
                    ];
                }
            } else {
                $state['word'] = null;
                $state['allClues'] = [];
            }
        }
        if ($room['phase'] === 'guessing') {
            $state['visibleClues'] = [];
            foreach (($room['clues'] ?? []) as $pid => $clue) {
                if (in_array($pid, $room['removedClues'] ?? [])) continue;
                $pname = '?';
                foreach ($room['players'] as $p) { if ($p['id'] === $pid) $pname = $p['name']; }
                $state['visibleClues'][] = ['playerName' => $pname, 'clue' => $clue];
            }
            // Não-adivinhadores veem todas as pistas (com duplicadas marcadas)
            if (!$state['isGuesser']) {
                $state['word'] = $room['currentWord'];
                $state['allClues'] = [];
                foreach (($room['clues'] ?? []) as $pid => $clue) {
                    $pname = '?';
                    foreach ($room['players'] as $p) { if ($p['id'] === $pid) $pname = $p['name']; }
                    $state['allClues'][] = [
                        'playerName' => $pname,
                        'clue' => $clue,
                        'removed' => in_array($pid, $room['removedClues'] ?? []),
                    ];
                }
            }
        }
        if (in_array($room['phase'], ['result', 'gameover'])) {
            $state['word'] = $room['currentWord'];
            $state['guess'] = $room['guess'] ?? null;
            $state['guessCorrect'] = $room['guessCorrect'] ?? null;
            $state['allClues'] = [];
            foreach (($room['clues'] ?? []) as $pid => $clue) {
                $pname = '?';
                foreach ($room['players'] as $p) { if ($p['id'] === $pid) $pname = $p['name']; }
                $state['allClues'][] = [
                    'playerName' => $pname,
                    'clue' => $clue,
                    'removed' => in_array($pid, $room['removedClues'] ?? []),
                ];
            }
        }
    }

    if ($room['game'] === 'impostor') {
        $impIdx = $room['impostorIndex'] ?? -1;
        $impId = $room['players'][$impIdx]['id'] ?? '';
        $state['isImpostor'] = $impId === $playerId;
        // No fácil, o impostor vê a categoria. No médio/difícil, não.
        $diff = $room['difficulty'] ?? 'medium';
        $state['category'] = ($state['isImpostor'] && $diff !== 'easy') ? null : ($room['category'] ?? null);
        $state['readyCount'] = count($room['readyPlayers'] ?? []);
        $state['voteCount'] = count($room['votes'] ?? []);
        $state['myVote'] = $room['votes'][$playerId] ?? null;

        if (in_array($room['phase'], ['result', 'gameover', 'impostor_guess'])) {
            $state['impostorIndex'] = $impIdx;
        } else {
            $state['impostorIndex'] = -1;
        }

        if (in_array($room['phase'], ['show_role', 'discussion', 'voting'])) {
            $state['word'] = $state['isImpostor'] ? null : $room['currentWord'];
        }
        if ($room['phase'] === 'impostor_guess') {
            // Impostor NÃO pode ver a palavra enquanto tenta adivinhar
            $state['word'] = $state['isImpostor'] ? null : $room['currentWord'];
            $state['impostorCaught'] = $room['impostorCaught'] ?? false;
            $state['guess'] = $room['guess'] ?? null;
            $state['guessCorrect'] = $room['guessCorrect'] ?? null;
            $state['votedOut'] = $room['votedOut'] ?? null;
        }
        if (in_array($room['phase'], ['result', 'gameover'])) {
            $state['word'] = $room['currentWord'];
            $state['impostorCaught'] = $room['impostorCaught'] ?? false;
            $state['guess'] = $room['guess'] ?? null;
            $state['guessCorrect'] = $room['guessCorrect'] ?? null;
            $state['votedOut'] = $room['votedOut'] ?? null;
        }
    }

    return $state;
}

// ==================== CLEANUP OLD ROOMS ====================
foreach (glob("$roomsDir/*.json") as $f) {
    if (filemtime($f) < time() - 7200) @unlink($f); // 2h old
}

// ==================== ACTIONS ====================

switch ($action) {

// ---------- CREATE ROOM ----------
case 'create_room':
    $game = $_POST['game'] ?? '';
    $name = trim($_POST['name'] ?? '');
    if (!$name) error('Escreve o teu nome!');
    if (!in_array($game, ['justone', 'impostor'])) error('Jogo inválido!');

    $code = generateCode();
    $difficulty = $_POST['difficulty'] ?? 'medium';
    if (!in_array($difficulty, ['easy', 'medium', 'hard'])) $difficulty = 'medium';
    $room = [
        'code' => $code,
        'game' => $game,
        'host' => $playerId,
        'difficulty' => $difficulty,
        'players' => [['id' => $playerId, 'name' => $name, 'score' => 0]],
        'phase' => 'lobby',
        'round' => 0,
        'maxRounds' => 5,
        'removeDuplicates' => true,
        'guesserIndex' => -1,
        'currentWord' => null,
        'clues' => [],
        'removedClues' => [],
        'guess' => null,
        'guessCorrect' => null,
        'category' => null,
        'impostorIndex' => -1,
        'readyPlayers' => [],
        'votes' => [],
        'impostorCaught' => false,
        'votedOut' => null,
        'timerEnd' => null,
    ];
    saveRoom($room);
    respond(['ok' => true, 'code' => $code, 'state' => getSafeState($room, $playerId)]);
    break;

// ---------- JOIN ROOM ----------
case 'join_room':
    $name = trim($_POST['name'] ?? '');
    if (!$name) error('Escreve o teu nome!');
    if (strlen($roomCode) !== 4) error('Código deve ter 4 letras!');

    $room = loadRoomLocked($roomCode);
    if (!$room) error('Sala não encontrada!');
    if (count($room['players']) >= 15) error('Sala cheia!');

    // Check if player already in room (reconnect)
    $found = false;
    foreach ($room['players'] as &$p) {
        if ($p['id'] === $playerId) { $p['name'] = $name; $found = true; break; }
    }
    unset($p);

    if (!$found) {
        foreach ($room['players'] as $p) {
            if ($p['name'] === $name) error('Já existe alguém com esse nome!');
        }
        $room['players'][] = ['id' => $playerId, 'name' => $name, 'score' => 0];
    }
    saveRoom($room);
    respond(['ok' => true, 'code' => $roomCode, 'game' => $room['game'], 'state' => getSafeState($room, $playerId)]);
    break;

// ---------- GET STATE (polling) ----------
case 'get_state':
    if (!$roomCode) error('Código em falta!');

    // Ler primeiro sem lock (maioria dos polls não modifica nada)
    $room = loadRoom($roomCode);
    if (!$room) error('Sala não encontrada!');

    // Verificar se o jogador está na sala e atualizar versão
    $playerFound = false;
    $appVersion = $_POST['app_version'] ?? $_GET['app_version'] ?? null;
    foreach ($room['players'] as &$pp) {
        if ($pp['id'] === $playerId) {
            $playerFound = true;
            if ($appVersion && ($pp['version'] ?? '') !== $appVersion) {
                $pp['version'] = $appVersion;
                // Guardar versão (sem lock, best effort)
                $roomFile = $GLOBALS['roomsDir'] . '/' . strtoupper($roomCode) . '.json';
                @file_put_contents($roomFile, json_encode($room, JSON_UNESCAPED_UNICODE));
            }
            break;
        }
    }
    unset($pp);
    if (!$playerFound) error('Não estás nesta sala!');

    // Verificar se timer expirou (precisa de modificar)
    $now = round(microtime(true) * 1000);
    if ($room['timerEnd'] && $now > $room['timerEnd']) {
        // Re-ler com lock exclusivo para modificar atomicamente
        $room = loadRoomLocked($roomCode);
        if (!$room) error('Sala não encontrada!');

        $changed = false;
        $now = round(microtime(true) * 1000);

        if ($room['timerEnd'] && $now > $room['timerEnd']) {
            // Just One: tempo de escrita expirou → avançar para revisão
            if ($room['game'] === 'justone' && $room['phase'] === 'writing') {
                justoneCheckDuplicates($room);
                $room['phase'] = 'review';
                $room['timerEnd'] = null;
                $changed = true;
            }
            // Just One: tempo de adivinhar expirou → conta como "passou"
            if ($room['game'] === 'justone' && $room['phase'] === 'guessing') {
                $room['guess'] = '(tempo esgotado)';
                $room['guessCorrect'] = false;
                $room['phase'] = 'result';
                $room['timerEnd'] = null;
                $changed = true;
            }
            // Just One: resultado com acerto → avançar automaticamente para próxima ronda
            if ($room['game'] === 'justone' && $room['phase'] === 'result' && ($room['guessCorrect'] ?? false)) {
                if ($room['round'] >= $room['maxRounds']) {
                    $room['phase'] = 'gameover';
                } else {
                    justoneNewRound($room);
                }
                $room['timerEnd'] = null;
                $changed = true;
            }
            // Impostor: tempo de discussão expirou → avançar para votação
            if ($room['game'] === 'impostor' && $room['phase'] === 'discussion') {
                $room['phase'] = 'voting';
                $room['timerEnd'] = round(microtime(true) * 1000) + 30000;
                $changed = true;
            }
        }

        if ($changed) {
            saveRoom($room);
        } else {
            unlockRoom();
        }
    }

    respond(['ok' => true, 'state' => getSafeState($room, $playerId)]);
    break;

// ---------- SET DIFFICULTY (lobby only) ----------
case 'set_difficulty':
    $difficulty = $_POST['difficulty'] ?? 'medium';
    if (!in_array($difficulty, ['easy', 'medium', 'hard'])) error('Dificuldade inválida!');
    $room = loadRoomLocked($roomCode);
    if (!$room) error('Sala não encontrada!');
    if ($room['host'] !== $playerId) error('Só o anfitrião pode mudar!');
    $room['difficulty'] = $difficulty;
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- SET ROUNDS ----------
case 'set_rounds':
    $rounds = intval($_POST['rounds'] ?? 5);
    if ($rounds < 1 || $rounds > 20) error('Número de rondas inválido!');
    $room = loadRoomLocked($roomCode);
    if (!$room) error('Sala não encontrada!');
    if ($room['host'] !== $playerId) error('Só o anfitrião pode mudar!');
    $room['maxRounds'] = $rounds;
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- SET REMOVE DUPLICATES ----------
case 'set_remove_duplicates':
    $remove = filter_var($_POST['remove_duplicates'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    $room = loadRoomLocked($roomCode);
    if (!$room) error('Sala não encontrada!');
    if ($room['host'] !== $playerId) error('Só o anfitrião pode mudar!');
    $room['removeDuplicates'] = $remove;
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- START GAME ----------
case 'start_game':
    $room = loadRoomLocked($roomCode);
    if (!$room) error('Sala não encontrada!');
    if ($room['host'] !== $playerId) error('Só o anfitrião pode começar!');
    if (count($room['players']) < 2) error('Precisas de pelo menos 2 jogadores!');

    // Guardar jogadores ativos (quem estava quando o jogo começou)
    $room['activePlayers'] = array_map(function($p) { return $p['id']; }, $room['players']);

    if ($room['game'] === 'justone') {
        justoneNewRound($room);
    } else {
        impostorNewRound($room);
    }
    saveRoom($room);
    respond(['ok' => true, 'state' => getSafeState($room, $playerId)]);
    break;

// ---------- JUST ONE: Pick number (adivinhador escolhe 1-5) ----------
case 'justone_pick_number':
    $number = (int)($_POST['number'] ?? 0);
    if ($number < 1 || $number > 5) error('Escolhe um número de 1 a 5!');

    $room = loadRoomLocked($roomCode);
    if (!$room || $room['phase'] !== 'pick_number') error('Ação inválida!');

    $guesserId = $room['players'][$room['guesserIndex']]['id'] ?? '';
    if ($playerId !== $guesserId) error('Só o adivinhador pode escolher!');

    $room['chosenNumber'] = $number;
    $room['currentWord'] = $room['wordCard'][$number - 1];
    $room['phase'] = 'show_word';
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- JUST ONE: Ready (cada jogador confirma que viu a palavra) ----------
case 'justone_ready':
    $room = loadRoomLocked($roomCode);
    if (!$room || $room['phase'] !== 'show_word') error('Ação inválida!');

    $guesserId = $room['players'][$room['guesserIndex']]['id'] ?? '';
    // O adivinhador não precisa confirmar (não vê a palavra)
    if ($playerId !== $guesserId) {
        if (!in_array($playerId, $room['readyPlayers'])) {
            $room['readyPlayers'][] = $playerId;
        }
    }

    // Contar quantos jogadores não-adivinhadores existem
    $nonGuessers = array_filter($room['players'], function($p) use ($guesserId) { return $p['id'] !== $guesserId; });
    if (count($room['readyPlayers']) >= count($nonGuessers)) {
        $room['phase'] = 'writing';
        $room['timerEnd'] = round(microtime(true) * 1000) + 60000;
        $room['readyPlayers'] = [];
    }
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- JUST ONE: Submit clue ----------
case 'justone_clue':
    $clue = trim($_POST['clue'] ?? '');
    if (!$clue) error('Escreve uma pista!');
    if (strpos($clue, ' ') !== false) error('Apenas uma palavra!');

    $room = loadRoomLocked($roomCode);
    if (!$room || $room['phase'] !== 'writing') error('Ação inválida!');

    $guesserId = $room['players'][$room['guesserIndex']]['id'] ?? '';
    if ($playerId === $guesserId) error('Tu és o adivinhador!');

    // Validar que a pista não é derivada da palavra secreta
    if (isDerivative($clue, $room['currentWord'])) {
        error('Não podes usar a palavra secreta nem derivados dela!');
    }

    $room['clues'][$playerId] = $clue;

    // Check if all submitted → avançar para revisão (host confirma antes de adivinhar)
    $nonGuessers = array_filter($room['players'], function($p) use ($guesserId) { return $p['id'] !== $guesserId; });
    if (count($room['clues']) >= count($nonGuessers)) {
        justoneCheckDuplicates($room);
        $room['phase'] = 'review';
        $room['timerEnd'] = null;
    }
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- JUST ONE: Confirm review ----------
case 'justone_confirm_review':
    $room = loadRoomLocked($roomCode);
    if (!$room || $room['host'] !== $playerId || $room['phase'] !== 'review') error('Ação inválida!');
    $room['phase'] = 'guessing';
    $room['timerEnd'] = round(microtime(true) * 1000) + 90000;
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- JUST ONE: Submit guess ----------
case 'justone_guess':
    $guess = trim($_POST['guess'] ?? '');
    if (!$guess) error('Escreve a tua resposta!');

    $room = loadRoomLocked($roomCode);
    if (!$room || $room['phase'] !== 'guessing') error('Ação inválida!');
    $guesserId = $room['players'][$room['guesserIndex']]['id'] ?? '';
    if ($playerId !== $guesserId) error('Não és o adivinhador!');

    $room['guess'] = $guess;
    $room['guessCorrect'] = matchesWord($guess, $room['currentWord']);

    if ($room['guessCorrect']) {
        foreach ($room['players'] as &$p) {
            if ($p['id'] === $guesserId) { $p['score'] += 1; }
            elseif (!in_array($p['id'], $room['removedClues'])) { $p['score'] += 1; }
        }
        unset($p);
    }
    $room['phase'] = 'result';
    // Se acertou, auto-avançar após 5 segundos
    $room['timerEnd'] = $room['guessCorrect']
        ? round(microtime(true) * 1000) + 5000
        : null;
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- JUST ONE: Skip ----------
case 'justone_skip':
    $room = loadRoomLocked($roomCode);
    if (!$room || $room['phase'] !== 'guessing') error('Ação inválida!');
    $room['guess'] = '(passou)';
    $room['guessCorrect'] = false;
    $room['phase'] = 'result';
    $room['timerEnd'] = null;
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- JUST ONE / IMPOSTOR: Next round ----------
case 'next_round':
    $room = loadRoomLocked($roomCode);
    if (!$room || $room['host'] !== $playerId) error('Ação inválida!');

    if ($room['round'] >= $room['maxRounds']) {
        $room['phase'] = 'gameover';
    } else {
        if ($room['game'] === 'justone') justoneNewRound($room);
        else impostorNewRound($room);
    }
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- IMPOSTOR: Ready ----------
case 'impostor_ready':
    $room = loadRoomLocked($roomCode);
    if (!$room || $room['phase'] !== 'show_role') error('Ação inválida!');

    if (!in_array($playerId, $room['readyPlayers'])) {
        $room['readyPlayers'][] = $playerId;
    }
    if (count($room['readyPlayers']) >= count($room['players'])) {
        $room['phase'] = 'discussion';
        $room['timerEnd'] = round(microtime(true) * 1000) + 120000;
        $room['readyPlayers'] = [];
    }
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- IMPOSTOR: Start vote ----------
case 'impostor_start_vote':
    $room = loadRoomLocked($roomCode);
    if (!$room || $room['host'] !== $playerId || $room['phase'] !== 'discussion') error('Ação inválida!');
    $room['phase'] = 'voting';
    $room['timerEnd'] = round(microtime(true) * 1000) + 30000;
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- IMPOSTOR: Vote ----------
case 'impostor_vote':
    $votedFor = $_POST['voted_for'] ?? '';
    $room = loadRoomLocked($roomCode);
    if (!$room || $room['phase'] !== 'voting') error('Ação inválida!');

    $room['votes'][$playerId] = $votedFor;

    if (count($room['votes']) >= count($room['players'])) {
        $voteCounts = [];
        foreach ($room['votes'] as $vid) {
            $voteCounts[$vid] = ($voteCounts[$vid] ?? 0) + 1;
        }
        $maxVotes = max($voteCounts);
        $mostVoted = array_keys(array_filter($voteCounts, function($v) use ($maxVotes) { return $v === $maxVotes; }));
        $impostorId = $room['players'][$room['impostorIndex']]['id'] ?? '';

        $room['votedOut'] = count($mostVoted) === 1 ? $mostVoted[0] : null;
        $room['impostorCaught'] = count($mostVoted) === 1 && $mostVoted[0] === $impostorId;

        if ($room['impostorCaught']) {
            $room['phase'] = 'impostor_guess';
        } else {
            // Impostor wins
            foreach ($room['players'] as &$p) {
                if ($p['id'] === $impostorId) $p['score'] += 3;
            }
            unset($p);
            $room['phase'] = 'result';
        }
        $room['timerEnd'] = null;
    }
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- IMPOSTOR: Final guess ----------
case 'impostor_final_guess':
    $guess = trim($_POST['guess'] ?? '');
    $room = loadRoomLocked($roomCode);
    if (!$room || $room['phase'] !== 'impostor_guess') error('Ação inválida!');
    $impostorId = $room['players'][$room['impostorIndex']]['id'] ?? '';
    if ($playerId !== $impostorId) error('Não és o impostor!');

    $room['guess'] = $guess;
    $room['guessCorrect'] = matchesWord($guess, $room['currentWord']);

    if ($room['guessCorrect']) {
        foreach ($room['players'] as &$p) {
            if ($p['id'] === $impostorId) $p['score'] += 2;
        }
    } else {
        foreach ($room['players'] as &$p) {
            if ($p['id'] !== $impostorId) $p['score'] += 2;
        }
    }
    unset($p);
    $room['phase'] = 'result';
    $room['timerEnd'] = null;
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- LEAVE ROOM ----------
case 'leave_room':
    $room = loadRoomLocked($roomCode);
    if (!$room) error('Sala não encontrada!');

    // Guardar ID do adivinhador antes de remover (para Just One)
    $guesserIdx = $room['guesserIndex'] ?? -1;
    $room['_guesser_id'] = $room['players'][$guesserIdx]['id'] ?? null;

    // Remover jogador da lista
    $room['players'] = array_values(array_filter($room['players'], function($p) use ($playerId) {
        return $p['id'] !== $playerId;
    }));

    // Se não sobrou ninguém, apagar a sala
    if (empty($room['players'])) {
        unlockRoom();
        $file = $GLOBALS['roomsDir'] . '/' . $roomCode . '.json';
        if (file_exists($file)) unlink($file);
        respond(['ok' => true, 'deleted' => true]);
    }

    // Se era o host, transferir para o próximo jogador
    if ($room['host'] === $playerId) {
        $room['host'] = $room['players'][0]['id'];
    }

    // Remover dos activePlayers também
    if (!empty($room['activePlayers'])) {
        $room['activePlayers'] = array_values(array_filter($room['activePlayers'], function($id) use ($playerId) {
            return $id !== $playerId;
        }));
    }

    // Limpar readyPlayers, clues e votes do jogador que saiu
    if (!empty($room['readyPlayers'])) {
        $room['readyPlayers'] = array_values(array_filter($room['readyPlayers'], function($id) use ($playerId) {
            return $id !== $playerId;
        }));
    }
    unset($room['clues'][$playerId]);
    unset($room['votes'][$playerId]);

    // Se estamos no lobby, apenas guardar
    // Se estamos em jogo, verificar se ainda há jogadores suficientes
    if ($room['phase'] !== 'lobby' && count($room['players']) < 2) {
        // Poucos jogadores, voltar ao lobby
        $room['phase'] = 'lobby';
        $room['round'] = 0;
        $room['guesserIndex'] = -1;
        $room['timerEnd'] = null;
        $room['readyPlayers'] = [];
        $room['wordCard'] = []; $room['chosenNumber'] = null; $room['currentWord'] = null;
        $room['clues'] = []; $room['removedClues'] = []; $room['guess'] = null; $room['guessCorrect'] = null;
        $room['impostorIndex'] = -1; $room['category'] = null; $room['votes'] = [];
        $room['activePlayers'] = [];
    }
    // Se ainda há jogadores suficientes, verificar se o jogo deve avançar de fase
    elseif ($room['phase'] !== 'lobby' && count($room['players']) >= 2) {

        if ($room['game'] === 'justone') {
            $guesserIdx = $room['guesserIndex'] ?? -1;
            // Verificar se o adivinhador ainda existe na lista de jogadores
            // (o jogador já foi removido neste ponto, então se o index aponta para outro ou está fora, o adivinhador saiu)
            $oldGuesserId = $room['_guesser_id'] ?? null; // guardado antes da remoção
            $currentGuesserExists = false;
            foreach ($room['players'] as $p) {
                if ($p['id'] === $oldGuesserId) { $currentGuesserExists = true; break; }
            }

            // Se o adivinhador saiu, reiniciar a ronda
            if ($oldGuesserId && $oldGuesserId === $playerId) {
                // Ajustar guesserIndex se necessário
                if ($guesserIdx >= count($room['players'])) {
                    $room['guesserIndex'] = count($room['players']) - 1;
                }
                // Reiniciar a ronda com novo adivinhador
                $room['round']--;
                justoneNewRound($room);
            } else {
                // Ajustar guesserIndex se jogador removido estava antes do adivinhador
                $guesserId = $oldGuesserId;
                $newGuesserIdx = -1;
                foreach ($room['players'] as $i => $p) {
                    if ($p['id'] === $guesserId) { $newGuesserIdx = $i; break; }
                }
                if ($newGuesserIdx >= 0) {
                    $room['guesserIndex'] = $newGuesserIdx;
                }

                $nonGuessers = array_filter($room['players'], function($p) use ($guesserId) { return $p['id'] !== $guesserId; });

                // show_word: verificar se todos os não-adivinhadores viram a palavra
                if ($room['phase'] === 'show_word' && count($room['readyPlayers']) >= count($nonGuessers)) {
                    $room['phase'] = 'writing';
                    $room['timerEnd'] = round(microtime(true) * 1000) + 60000;
                    $room['readyPlayers'] = [];
                }

                // writing: verificar se todas as pistas foram recebidas → revisão
                if ($room['phase'] === 'writing' && count($room['clues'] ?? []) >= count($nonGuessers)) {
                    justoneCheckDuplicates($room);
                    $room['phase'] = 'review';
                    $room['timerEnd'] = null;
                }
            }
        }

        if ($room['game'] === 'impostor') {
            // show_role: verificar se todos estão prontos
            if ($room['phase'] === 'show_role' && count($room['readyPlayers']) >= count($room['players'])) {
                $room['phase'] = 'discussion';
                $room['timerEnd'] = round(microtime(true) * 1000) + 120000;
                $room['readyPlayers'] = [];
            }

            // voting: verificar se todos votaram
            if ($room['phase'] === 'voting' && count($room['votes'] ?? []) >= count($room['players'])) {
                $voteCounts = [];
                foreach ($room['votes'] as $vid) {
                    $voteCounts[$vid] = ($voteCounts[$vid] ?? 0) + 1;
                }
                $maxVotes = max($voteCounts);
                $mostVoted = array_keys(array_filter($voteCounts, function($v) use ($maxVotes) { return $v === $maxVotes; }));
                $impostorId = $room['players'][$room['impostorIndex']]['id'] ?? '';

                $room['votedOut'] = count($mostVoted) === 1 ? $mostVoted[0] : null;
                $room['impostorCaught'] = count($mostVoted) === 1 && $mostVoted[0] === $impostorId;

                if ($room['impostorCaught']) {
                    $room['phase'] = 'impostor_guess';
                } else {
                    foreach ($room['players'] as &$p) {
                        if ($p['id'] === $impostorId) $p['score'] += 3;
                    }
                    unset($p);
                    $room['phase'] = 'result';
                }
                $room['timerEnd'] = null;
            }

            // Se o impostor saiu durante impostor_guess, inocentes ganham
            if ($room['phase'] === 'impostor_guess') {
                $impostorId = $room['players'][$room['impostorIndex']]['id'] ?? null;
                if (!$impostorId) {
                    $room['guess'] = '(impostor saiu)';
                    $room['guessCorrect'] = false;
                    foreach ($room['players'] as &$p) { $p['score'] += 2; }
                    unset($p);
                    $room['phase'] = 'result';
                    $room['timerEnd'] = null;
                }
            }
        }
    }

    unset($room['_guesser_id']);
    saveRoom($room);
    respond(['ok' => true, 'newHost' => $room['host']]);
    break;

// ---------- NEW GAME ----------
case 'new_game':
    $room = loadRoomLocked($roomCode);
    if (!$room || $room['host'] !== $playerId) error('Ação inválida!');
    $room['round'] = 0;
    $room['guesserIndex'] = -1;
    foreach ($room['players'] as &$p) $p['score'] = 0;
    unset($p);
    $room['phase'] = 'lobby';
    $room['timerEnd'] = null;
    $room['readyPlayers'] = [];
    // Limpar estado Just One
    $room['wordCard'] = []; $room['chosenNumber'] = null; $room['currentWord'] = null;
    $room['clues'] = []; $room['removedClues'] = []; $room['guess'] = null; $room['guessCorrect'] = null;
    // Limpar estado Impostor
    $room['impostorIndex'] = -1; $room['category'] = null; $room['votes'] = [];
    saveRoom($room);
    respond(['ok' => true]);
    break;

// ---------- RESTART GAME (recomeçar a meio sem ir ao lobby) ----------
case 'restart_game':
    $room = loadRoomLocked($roomCode);
    if (!$room || $room['host'] !== $playerId) error('Ação inválida!');
    // Atualizar dificuldade se enviada
    $newDiff = $_POST['difficulty'] ?? null;
    if ($newDiff && in_array($newDiff, ['easy', 'medium', 'hard'])) {
        $room['difficulty'] = $newDiff;
    }
    $room['round'] = 0;
    $room['guesserIndex'] = -1;
    foreach ($room['players'] as &$p) $p['score'] = 0;
    unset($p);
    $room['timerEnd'] = null;
    $room['readyPlayers'] = [];
    // Limpar estado
    $room['wordCard'] = []; $room['chosenNumber'] = null; $room['currentWord'] = null;
    $room['clues'] = []; $room['removedClues'] = []; $room['guess'] = null; $room['guessCorrect'] = null;
    $room['impostorIndex'] = -1; $room['category'] = null; $room['votes'] = [];
    // Iniciar primeira ronda diretamente
    if ($room['game'] === 'justone') {
        justoneNewRound($room);
    } elseif ($room['game'] === 'impostor') {
        impostorNewRound($room);
    }
    saveRoom($room);
    respond(['ok' => true]);
    break;

default:
    error('Ação desconhecida: ' . $action);
}

// ==================== GAME LOGIC FUNCTIONS ====================

function justoneNewRound(&$room) {
    global $JUSTONE_WORDS;
    $room['round']++;
    $room['phase'] = 'pick_number';
    $n = count($room['players']);
    if ($room['guesserIndex'] < 0) {
        // Primeira ronda: escolher aleatoriamente
        $room['guesserIndex'] = random_int(0, $n - 1);
    } else {
        $room['guesserIndex'] = ($room['guesserIndex'] + 1) % $n;
    }

    // Carregar palavras já usadas globalmente (todas as salas)
    $usedWordsFile = $GLOBALS['roomsDir'] . '/used_words_justone.json';
    $usedWords = [];
    if (file_exists($usedWordsFile)) {
        $usedData = json_decode(file_get_contents($usedWordsFile), true);
        if (is_array($usedData)) {
            // Limpar palavras com mais de 24h (para não crescer infinitamente)
            $cutoff = time() - 86400;
            foreach ($usedData as $word => $ts) {
                if ($ts > $cutoff) $usedWords[$word] = $ts;
            }
        }
    }

    // Gerar 5 palavras aleatórias da dificuldade escolhida (sem repetir)
    $diff = $room['difficulty'] ?? 'medium';
    $words = $JUSTONE_WORDS[$diff] ?? $JUSTONE_WORDS['medium'];
    // Filtrar palavras já usadas
    $available = array_filter($words, function($w) use ($usedWords) {
        return !isset($usedWords[mb_strtolower($w)]);
    });
    // Se restam menos de 5, resetar (já usou quase todas)
    if (count($available) < 5) {
        $available = $words;
        $usedWords = [];
    }
    $available = array_values($available);
    $keys = array_rand($available, 5);
    $room['wordCard'] = [];
    foreach ($keys as $k) {
        $room['wordCard'][] = $available[$k];
        $usedWords[mb_strtolower($available[$k])] = time();
    }

    // Guardar palavras usadas
    file_put_contents($usedWordsFile, json_encode($usedWords));

    $room['chosenNumber'] = null;
    $room['currentWord'] = null;
    $room['clues'] = [];
    $room['removedClues'] = [];
    $room['guess'] = null;
    $room['guessCorrect'] = null;
    $room['timerEnd'] = null;
    $room['readyPlayers'] = [];
}

function justoneCheckDuplicates(&$room) {
    $room['removedClues'] = [];
    $removeDup = $room['removeDuplicates'] ?? true;

    // Sempre verificar derivados da palavra secreta (mesmo sem remoção de duplicadas)
    foreach ($room['clues'] as $pid => $clue) {
        if (isDerivative($clue, $room['currentWord'])) {
            $room['removedClues'][] = $pid;
        }
    }

    // Só remover duplicadas se a opção estiver ativa
    if ($removeDup) {
        $clueMap = [];
        foreach ($room['clues'] as $pid => $clue) {
            if (in_array($pid, $room['removedClues'])) continue;
            $normalized = normalize($clue);
            $clueMap[$normalized][] = $pid;
        }
        foreach ($clueMap as $pids) {
            if (count($pids) > 1) {
                $room['removedClues'] = array_merge($room['removedClues'], $pids);
            }
        }

        // Agrupar por raiz (stem) - pistas com a mesma raiz são duplicadas
        $stems = [];
        foreach ($room['clues'] as $pid => $clue) {
            if (in_array($pid, $room['removedClues'])) continue;
            $stem = getStem($clue);
            $stems[$stem][] = $pid;
        }
        foreach ($stems as $pids) {
            if (count($pids) > 1) {
                foreach ($pids as $pid) {
                    if (!in_array($pid, $room['removedClues'])) {
                        $room['removedClues'][] = $pid;
                    }
                }
            }
        }
    }
}

function impostorNewRound(&$room) {
    global $IMPOSTOR_CATEGORIES;
    $room['round']++;
    $room['phase'] = 'show_role';
    $diff = $room['difficulty'] ?? 'medium';
    $cats = $IMPOSTOR_CATEGORIES[$diff] ?? $IMPOSTOR_CATEGORIES['medium'];

    // Carregar palavras já usadas globalmente
    $usedWordsFile = $GLOBALS['roomsDir'] . '/used_words_impostor.json';
    $usedWords = [];
    if (file_exists($usedWordsFile)) {
        $usedData = json_decode(file_get_contents($usedWordsFile), true);
        if (is_array($usedData)) {
            $cutoff = time() - 86400;
            foreach ($usedData as $word => $ts) {
                if ($ts > $cutoff) $usedWords[$word] = $ts;
            }
        }
    }

    // Escolher categoria e palavra que não foi usada recentemente
    $attempts = 0;
    do {
        $cat = $cats[array_rand($cats)];
        $availableWords = array_filter($cat['palavras'], function($w) use ($usedWords) {
            return !isset($usedWords[mb_strtolower($w)]);
        });
        $attempts++;
        // Se tentou muitas vezes, resetar palavras usadas
        if ($attempts > 20) {
            $usedWords = [];
            $availableWords = $cat['palavras'];
        }
    } while (empty($availableWords) && $attempts <= 20);

    $availableWords = array_values($availableWords);
    $chosenWord = $availableWords[array_rand($availableWords)];
    $usedWords[mb_strtolower($chosenWord)] = time();
    file_put_contents($usedWordsFile, json_encode($usedWords));

    $room['category'] = $cat['categoria'];
    $room['currentWord'] = $chosenWord;
    $room['impostorIndex'] = random_int(0, count($room['players']) - 1);
    $room['readyPlayers'] = [];
    $room['votes'] = [];
    $room['guess'] = null;
    $room['guessCorrect'] = null;
    $room['impostorCaught'] = false;
    $room['votedOut'] = null;
    $room['timerEnd'] = null;
}
