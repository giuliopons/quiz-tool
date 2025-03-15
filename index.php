<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzone</title>
    <link rel="stylesheet" href="style.css">
    <script>
        const questions = <?php include('domande.json'); ?>;
        const correctAnswers = <?php include('risposte.json'); ?>;
        let currentQuestion = 0;
        let score = 0;
        let playerName = "";
        let correctCount = 0;

        function startQuiz() {
            playerName = document.getElementById('player-select').value;
            if (!playerName) {
                alert("Seleziona il tuo nome prima di iniziare!");
                return;
            }
            document.getElementById('player-selection').style.display = 'none';
            document.getElementById('start-btn').style.display = 'none';
            document.getElementById('quiz-interface').classList.remove('hidden');
            showQuestion();
        }

        const loade = ['🤯','🧠','💀','🤓']

        function showQuestion() {

            randomEmoji = Math.floor(Math.random() * loade.length);

            document.getElementById('question-container').innerHTML = "<span>" + loade[randomEmoji] +"</span>";
            document.getElementById('rispostina').innerHTML = '';
            document.querySelectorAll('button').forEach(button => button.classList.add('hidden'));

            // wait 2 seconds than show the new question
            new Promise(resolve => setTimeout(resolve, 1000))
                .then(() => {
                    document.querySelectorAll('button').forEach(button => {
                        button.classList.remove('hidden');
                        button.classList.remove('sbagliato');
                        button.classList.remove('corretto');
                        button.classList.remove('locked');
                        button.classList.remove('blink_me');
						button.classList.remove('sel');
                        
                    });
                    document.getElementById('rispostina').innerHTML = '';
                    document.getElementById('question-container').innerHTML = 
                    `<h3>Domanda ${currentQuestion + 1} di ${questions.length}</h3><p>${questions[currentQuestion]}</p>`;
                });

           
        }

        function submitAnswer(answer) {
            if(document.getElementById('vero').classList.contains('locked')) {
                console.log('locked');
                return;
            }
            document.getElementById('vero').classList.add('locked');
            document.getElementById('falso').classList.add('locked');
            
            
                if(answer === correctAnswers[currentQuestion]) {
                    if( answer === true) {
                        document.getElementById('vero').classList.add('corretto');
						document.getElementById('vero').classList.add('sel');
                    } else {
                        document.getElementById('falso').classList.add('corretto');
						document.getElementById('falso').classList.add('sel');
                    }
                }
                if(answer !== correctAnswers[currentQuestion]) {
                    if( answer === true) {
                        document.getElementById('falso').classList.add('corretto');
                        document.getElementById('falso').classList.add('blink_me');
						document.getElementById('vero').classList.add('sel');
                    } else {
                        document.getElementById('vero').classList.add('corretto');
                        document.getElementById('vero').classList.add('blink_me');
						document.getElementById('falso').classList.add('sel');
                    }
                    
                }
                

            if (answer === correctAnswers[currentQuestion]) {
                
                score += 10;
                correctCount++;
                // document.getElementById('rispostina').style.color = 'green';
                document.getElementById('rispostina').innerHTML = "BENE!";
            } else {
                // document.getElementById('rispostina').style.color = 'red';
                // document.getElementById('rispostina').innerHTML = (answer ? 'VERO' : 'FALSO' ) + ' è SBAGLIATO!';
            }
            currentQuestion++;
            // wait 5 seconds than show the new question or send result
            new Promise(resolve => setTimeout(resolve, 3000))
                .then(() => {
                    if (currentQuestion < questions.length) {
                        showQuestion();
                    } else {
                        sendResults();
                    }
                });

        }

        function sendResults() {
            fetch('quizapi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: playerName, score: score, correctAnswers: correctCount })
            }).then(response => response.json())
              .then(data => function(){

              })
              .catch(error => console.error('Errore invio dati:', error));

            document.getElementById('quiz-interface').classList.add('hidden');
            document.getElementById('results').classList.remove('hidden');
            document.getElementById('SCORE').innerHTML = score;

            document.getElementById('final-score').innerHTML = 
                `${playerName}, hai risposto correttamente a ${correctCount} domande su ${questions.length}`;
        }
    </script>
</head>
<body id='stud'>
    <h1 class='rainbow rainbow_text_animated'>IL QUIZZONE!</h1>
    <div id="player-selection">
        <h2>Seleziona il tuo nome</h2>
        <select id="player-select">
            <option value="">-- Scegli il tuo nome --</option>
            <option value="Andrea">Andrea</option>
            <option value="Arianna">Arianna</option>
            <option value="Davide">Davide</option>
            <option value="Edoardo">Edoardo</option>
            <option value="Gabriele">Gabriele</option>
            <option value="GabrieleB">GabrieleB</option>
            <option value="Jacopo">Jacopo</option>
            <option value="Lorenzo">Lorenzo</option>
            <option value="Mattia">Mattia</option>
            <option value="MattiaN">MattiaN</option>
        </select>
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
