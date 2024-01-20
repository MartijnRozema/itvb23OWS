<?php

namespace Classes;

class Game
{
    private DatabaseHandler $databaseHandler;

    private int $gameId;
    private int $player;
    private array $hand;
    private array $board;
    private int $prevMoveId;
    private string $error;

    public function __construct(DatabaseHandler $databaseHandler = null) {
        $this->databaseHandler = $databaseHandler ?? new DatabaseHandler();
    }

    public function executeAction(): void {
        if (!isset($_POST["action"])) {
            return;
        }

        $this->initializeGame();

        switch ($_POST["action"]) {
            case "play":
                $this->play();
                break;
            case "move":
                $this->move();
                break;
            case "pass":
                $this->pass();
                break;
            case "undo":
                $this->undo();
                break;
            case "restart":
                $this->restart();
                break;
        }

        $this->updateSession();
        $this->reloadPage();
    }

    public function getAvailablePieces(): array {
        return [];
    }

    public function getValidMoves(): array {
        return [];
    }

    private function reloadPage(): void {
        header("Location: index.php");
    }

    private function play(): void {

    }

    private function move(): void {

    }

    private function pass(): void {

    }

    private function undo(): void {

    }

    private function restart(): void {
        $this->board = [];
        $this->hand = [0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3], 1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]];
        $this->player = 0;
        $this->gameId = $this->databaseHandler->restartGame();
    }

    private function initializeGame(): void {
        $this->gameId = $_SESSION["game_id"];
        $this->player = $_SESSION["player"];
        $this->hand = $_SESSION["hand"];
        $this->board = $_SESSION["board"];
        $this->prevMoveId = $_SESSION["last_move"];
        $this->error = $_SESSION["error"];
    }

    private function updateSession(): void {
        $_SESSION["game_id"] = $this->gameId;
        $_SESSION["player"] = $this->player;
        $_SESSION["hand"] = $this->hand;
        $_SESSION["board"] = $this->board;
        $_SESSION["last_move"] = $this->prevMoveId;
        $_SESSION["error"] = $this->error;
    }
}