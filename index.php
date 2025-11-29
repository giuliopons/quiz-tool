<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzone</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
<body id='stud'>
    <h1 id='big-title' class='rainbow rainbow_text_animated'>IL QUIZZONE!</h1>
    <div id="topic-selection">
        <h2>Seleziona l'argomento</h2>
        <select id="topic-select">
            <option value="">-- Scegli --</option>
            <?php
            $files = glob('./topics/*.json');
            foreach ($files as $file) {
                $topicName = basename($file, '.json');
                echo "<option value='$topicName'>$topicName</option>";
            }
            ?>
        </select>
        <div class='divider'>💥</div>
    </div>
    <div id="player-selection">
        <h2>Seleziona il tuo nome</h2>
        <select id="player-select">
            <option value="">-- Scegli --</option>
        </select>
        <div class='divider'>⭐</div>
    </div>
    <button id="start-btn" onclick="startQuiz()">Inizia Quiz</button>
    <div id="quiz-interface" class="hidden">
        <div id="question-container"></div>
        <button onclick="submitAnswer(true)" id='vero'>VERO</button>
        <button onclick="submitAnswer(false)" id='falso'>FALSO</button>
        <div id="rispostina"></div>
    </div>
    <div id="results" class="hidden">
        <div id="SCORE"></div>
        <div id="final-score"></div>
        <button onclick="document.location.href =document.location.href ;">RIGIOCA</button>
        <br><br>
        <a href="top10.php" class="textlink">CLASSIFICA</a>
    </div>
</body>
</html>
