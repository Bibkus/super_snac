const canvas = document.getElementById("gameCanvas");
const ctx = canvas.getContext("2d");

let box = 20;
let snake = [{ x: 10 * box, y: 10 * box }];
let direction = "RIGHT";
let gameRunning = false;
let score = 0;
let speed = 150;
let gameLoop;

let food = generateFood();
let blueFood = generateFood(true);

document.addEventListener("keydown", changeDirection);
document.addEventListener("keydown", (event) => {
    if (event.key === "Enter" && !gameRunning) {
        gameRunning = true;
        gameLoop = setInterval(updateGame, speed);
    }
});

function changeDirection(event) {
    let key = event.key;
    if (key === "ArrowUp" && direction !== "DOWN") direction = "UP";
    else if (key === "ArrowDown" && direction !== "UP") direction = "DOWN";
    else if (key === "ArrowLeft" && direction !== "RIGHT") direction = "LEFT";
    else if (key === "ArrowRight" && direction !== "LEFT") direction = "RIGHT";
}

function updateGame() {
    let head = { x: snake[0].x, y: snake[0].y };

    if (direction === "UP") head.y -= box;
    if (direction === "DOWN") head.y += box;
    if (direction === "LEFT") head.x -= box;
    if (direction === "RIGHT") head.x += box;

    if (head.x === food.x && head.y === food.y) {
        score++;
        food = generateFood();


        speed = Math.max(50, speed * 0.995);
        clearInterval(gameLoop);
        gameLoop = setInterval(updateGame, speed);
    } else if (head.x === blueFood.x && head.y === blueFood.y) {
        score = Math.max(0, score - 1);
        snake.push({}, {});
        blueFood = generateFood(true);
    } else {
        snake.pop();
    }

    if (checkCollision(head)) return gameOver();

    snake.unshift(head);
    drawGame();
}



function checkCollision(head) {
    return snake.some(segment => segment.x === head.x && segment.y === head.y) ||
        head.x < 0 || head.x >= canvas.width || head.y < 0 || head.y >= canvas.height;
}

function drawGame() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);


    ctx.fillStyle = snakeColor;
    for (let i = 0; i < snake.length; i++) {
        ctx.fillRect(snake[i].x, snake[i].y, box, box);
    }

    // Рисуем еду
    ctx.fillStyle = "red";
    ctx.fillRect(food.x, food.y, box, box);

    ctx.fillStyle = "blue";
    ctx.fillRect(blueFood.x, blueFood.y, box, box);
}



function lightenColor(color, percent) {
    let num = parseInt(color.slice(1), 16),
        amt = Math.round(2.55 * percent),
        r = (num >> 16) + amt,
        g = (num >> 8 & 0x00FF) + amt,
        b = (num & 0x0000FF) + amt;

    return "#" + (0x1000000 + (r < 255 ? r : 255) * 0x10000 + (g < 255 ? g : 255) * 0x100 + (b < 255 ? b : 255)).toString(16).slice(1).toUpperCase();
}

function generateFood(isBlue = false) {
    let newFood;
    do {
        newFood = { x: Math.floor(Math.random() * (canvas.width / box)) * box, y: Math.floor(Math.random() * (canvas.height / box)) * box };
    } while (snake.some(segment => segment.x === newFood.x && segment.y === newFood.y) || (isBlue && newFood.x === food.x && newFood.y === food.y));

    return newFood;
}

function gameOver() {
    clearInterval(gameLoop);
    fetch('/save-score', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ username, score }) })
        .then(() => window.location.href = "/leaderboard");
}
