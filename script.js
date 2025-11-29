let questions = [];
let correctAnswers = [];

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

function populatePlayerSelect() {
    fetch('players.json')
        .then(response => response.json())
        .then(players => {
            const select = document.getElementById('player-select');
            players.forEach(player => {
                const option = document.createElement('option');
                option.value = player.name;
                option.text = player.name;
                select.appendChild(option);
            });
        });
}

function populateQuizData() {
    fetch('quizdata.json')
    .then(response => response.json())
    .then(data => {
        const questionsAndAnswers = data;
        questions = questionsAndAnswers.map(qa => qa.question);
        correctAnswers = questionsAndAnswers.map(qa => qa.answer);
    })
    .catch(error => {
        console.error('Errore caricamento dati:', error);
    });

}

window.onload = function() {
    populatePlayerSelect();

    populateQuizData();

}