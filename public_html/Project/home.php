<?php
require(__DIR__ . "/../../partials/nav.php");
?>
<br>
<?php
if (is_logged_in()) {
    echo "Welcome home, " . get_user_email();
    //comment this out if you don't want to see the session variables
    // echo "<pre>" . var_export($_SESSION, true) . "</pre>";
} else {
    echo "You're not logged in";
}
?>
<br>

<style>
    #canvas {
        width: 600px;
        height: 400px;
        border: 1px solid black;
        margin: 0 auto;
        display: block;
    }

</style>

<canvas id="canvas" width="600" height="400" tabindex="1"></canvas>

<script>
    // Collect The Square game

    // Get a reference to the canvas DOM element
    var canvas = document.getElementById('canvas');
    // Get the canvas drawing context
    var context = canvas.getContext('2d');

    let secondsPassed = 0;
    let oldTimeStamp = 0;
    let fps = 0;

    // Your score
    var score = 0;

    // size of snake segments and food
    var blockSize = 20;

    // Properties for your square
    var snakeX = 50; // X position
    var snakeY = 100; // Y position
    var snakeXChange = 0; // rate of change for X position
    var snakeYChange = 0; // rate of change for X position
    var snakeSegments = [] // array of snake segments containing positions
    var snakeLength = 1 // starting snake length

    // Properties for the target square
    var foodX = 0;
    var foodY = 0;

    // Determine if number a is within the range b to c (exclusive)
    function isWithin(a, b, c) {
        return (a > b && a < c);
    }

    // Countdown timer (in seconds)
    var countdown = 5;
    // ID to track the setTimeout
    var id = null;

    // Listen for keydown events to move the snake
    canvas.addEventListener('keydown', function(event) {
        event.preventDefault();
        if (event.keyCode === 83) { // s
            snakeXChange = 0
            snakeYChange = blockSize
        }
        if (event.keyCode === 87) { // w
            snakeXChange = 0
            snakeYChange = -blockSize
        }
        if (event.keyCode === 65) { // a
            snakeXChange = -blockSize
            snakeYChange = 0
        }
        if (event.keyCode === 68) { // d
            snakeXChange = blockSize
            snakeYChange = 0
        }
    });

    // Show the start menu
    function menu() {
        erase();
        context.fillStyle = '#000000';
        context.font = '36px Arial';
        context.textAlign = 'center';
        context.fillText('Snake Game!', canvas.width / 2, canvas.height / 4);
        context.font = '24px Arial';
        context.fillText('Click to Start', canvas.width / 2, canvas.height / 2);
        context.fillText("Collect as many points as you can in 30 seconds", canvas.width / 2, canvas.height / 3)
        context.font = '18px Arial'
        context.fillText('Use WASD keys to move', canvas.width / 2, (canvas.height / 4) * 3);
        // Start the game on a click
        canvas.addEventListener('click', startGame);
    }

    // Start the game
    function startGame() {
        // Reduce the countdown timer ever second
        id = setInterval(function() {
            countdown--;
        }, 1000);
        // Stop listening for click events
        canvas.removeEventListener('click', startGame);
        // Put the target at a random starting point
        moveFood();
        // Kick off the draw loop
        window.requestAnimationFrame(draw);
    }

    // Show the game over screen
    function endGame() {
        // Stop the countdown
        clearInterval(id);
        // Display the final score
        erase();
        context.fillStyle = '#000000';
        context.font = '24px Arial';
        context.textAlign = 'center';
        context.fillText('Final Score: ' + score, canvas.width / 2, canvas.height / 2);

        console.log(score)

        var data = new FormData();
        data.append("score", score)

        console.log(data)

        fetch("api/save_score.php", {
            method: "POST",
            headers: {
                "Content-type": "application/x-www-form-urlencoded",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: data
        }).then(async res => {
            let data = await res.json();
            console.log("received data", data);
            console.log("saved score");
            // window.location.reload(); // reload the webpage for new game
        })
    }

    // Move the food to a random position 
    function moveFood() {
        foodX = Math.floor((Math.random() * (canvas.width - blockSize)) / blockSize) * blockSize + blockSize;
        foodY = Math.floor((Math.random() * (canvas.height - blockSize)) / blockSize) * blockSize ;

        // console.log(foodX, foodY);
    }

    // Clear the canvas
    function erase() {
        context.fillStyle = '#FFFFFF';
        context.fillRect(0, 0, 600, 400);
    }

    // move each segment of the snake
    function moveSnake(){
        snakeSegments.forEach((segment) => {
            // draw the segment
            context.fillStyle = '#3644E4';
            context.fillRect(segment[0], segment[1], blockSize, blockSize);
        })
    }

    // The main draw loop
    function draw(timeStamp) {
        secondsPassed = (timeStamp - oldTimeStamp) / 50;
        // secondsPassed = Math.min(secondsPassed, 0.1);
        oldTimeStamp = timeStamp;

        erase();

        // move the snake continuously accordingly to the last inputted direction
        snakeX += snakeXChange * secondsPassed;
        snakeY += snakeYChange * secondsPassed;

        // add a new segment to the snakeSegments array according to current position
        let snakeSegment = [snakeX, snakeY]
        snakeSegments.push(snakeSegment)

        // remove the first segment of the snake if the length of it has not increased
        if (snakeSegments.length > snakeLength){
            snakeSegments.shift()
        }

        // Keep the square within the bounds
        if (snakeY + blockSize > canvas.height) {
            snakeY = canvas.height - blockSize;
        }
        if (snakeY < 0) {
            snakeY = 0;
        }
        if (snakeX < 0) {
            snakeX = 0;
        }
        if (snakeX + blockSize > canvas.width) {
            snakeX = canvas.width - blockSize;
        }

        // check is snake is colliding with food
        if (isWithin(foodX, snakeX, snakeX + blockSize) || isWithin(foodX + blockSize, snakeX, snakeX + blockSize)) { // X
            if (isWithin(foodY, snakeY, snakeY + blockSize) || isWithin(foodY + blockSize, snakeY, snakeY + blockSize)) { // Y
            // Respawn the target
            moveFood();
            // Increase the score
            score++;
            snakeLength++;
            }
        }

        // move the snake
        moveSnake();

        // Draw the food
        context.fillStyle = '#09804C';
        context.fillRect(foodX, foodY, blockSize, blockSize);
        // Draw the score and time remaining
        context.fillStyle = '#000000';
        context.font = '24px Arial';
        context.textAlign = 'left';
        context.fillText('Score: ' + score, 10, 24);
        context.fillText('Time Remaining: ' + countdown, 10, 50);
        
        // End the game or keep playing
        if (countdown <= 0) {
            endGame();
        } else {
            window.requestAnimationFrame(draw);
        }
    }

    // Start the game
    menu();
    canvas.focus();

</script>
<?php
require(__DIR__ . "/../../partials/flash.php");
?>

<!-- 

using ajax, make a post request for the score variable when the game ends

then in php, using session variables

-->