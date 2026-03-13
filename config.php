<?php

// include secrets, like API keys
include("secrets.php");

// if true allows the creation of new quizzes
// just by adding a raw topic and using AI
$ACTIVATE_CREATION = false;

// if true allows the player to choose a name
// by inserting it and not by a select list
$ACTIVATE_FREE_USER = false;

// list of allowed quiz topic slugs (folder names inside ./topics)
// example: $AVAILABLE_QUIZZES = ['arduino', 'demo'];
// if empty, all available topics are allowed
$AVAILABLE_QUIZZES = ['arduino-2026'];


// include common functions
include("libraries/common.php");
?>