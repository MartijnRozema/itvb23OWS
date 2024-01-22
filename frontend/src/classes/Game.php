<?php

namespace Classes;

$GLOBALS['OFFSETS'] = [[0, 1], [0, -1], [1, 0], [-1, 0], [-1, 1], [1, -1]];

class Game
{
    private DatabaseHandler $databaseHandler;

    private int $gameId;
    private int $player;
    private array $hand;
    private array $board;
    private int | null $prevMoveId;
    private int $turnCounter;
    private string | null $error;

    public function __construct(DatabaseHandler $databaseHandler = null) {
        $this->databaseHandler = $databaseHandler ?? new DatabaseHandler();
    }

    /**
     * Executes the specified action based on the received POST data, updating the game state accordingly.
     * Possible actions include "play," "move," "pass," "undo," "restart," and others.
     *
     * @return void
     */
    public function executeAction(): void {
        if (!isset($_POST["action"])) {
            return;
        }

        $this->initializeGame();

       $this->error = null;

        switch ($_POST["action"]) {
            case "play":
                $pos = $_POST["pos"];
                $piece = $_POST["piece"];
                $this->play($pos, $piece);
                break;
            case "move":
                $fromPos = $_POST["from"];
                $toPos = $_POST["to"];
                $this->move($fromPos, $toPos);
                break;
            case "pass":
                $this->pass();
                break;
            case "undo":
                $this->undo();
                break;
            case "restart":
                $this->restart();
                $this->reloadPage();
                return;
            default:
                $this->reloadPage();
                return;
        }

        $this->updateSession();
        $this->reloadPage();
    }

    /**
     * Retrieves an array of available pieces for the current player from their hand.
     *
     * @return array An array containing the keys of available pieces (pieces with a count greater than 0).
     */
    public function getAvailablePieces(): array {
        $availablePieces = [];
        foreach ($this->getHand() as $key => $value) {
            if ($value > 0) {
                $availablePieces[] = $key;
            }
        }

        sort($availablePieces);

        return $availablePieces;
    }

    /**
     * Retrieves the valid play moves for the current player based on the Hive game rules.
     *
     * @return array Returns an array containing the valid positions on the board where the current
     * player can place a piece.
     */
    public function getValidPlayMoves(): array {
        $this->initializeGame();

        $validMoves = [];
        foreach ($GLOBALS["OFFSETS"] as $offset) {
            foreach (array_keys($this->board) as $pos) {
                [$x1, $y1] = [$offset[0], $offset[1]];
                [$x2, $y2] = explode(",", $pos);

                $newPos = ($x1 + (int)$x2) . "," . ($y1 + (int)$y2);

                if ($this->turnCounter <= 1 ||
                    (!array_key_exists($newPos, $this->board) &&
                    $this->hasNeighbour($newPos) &&
                    $this->neighboursAreSameColor($this->player, $newPos))) {
                    $validMoves[] = $newPos;
                }
            }
        }

        return count($validMoves) > 0 ? array_unique($validMoves) : ["0,0"];
    }

    /**
     * Retrieves the positions on the board that are occupied by pieces of the current player.
     *
     * @return array Returns an array containing the positions on the board where pieces of the
     * current player are placed.
     */
    public function getOccupiedPositions(): array {
        $this->initializeGame();

        $positions = [];

        foreach ($this->board as $pos => $move) {
            if (!$move) {
                //No piece placed in this position
                continue;
            }

            $player = $move[count($move) - 1][0];

            if ($player === $this->player) {
                $positions[] = $pos;
            }
        }
        return $positions;
    }

    /**
     * Retrieves the hand of the specified player or the current player if no player is specified.
     *
     * @param int|null $player (Optional) The player whose hand is to be retrieved.
     * If not provided, the current player's hand is returned.
     * @return array Returns an associative array representing the hand of the specified player or
     * the current player.
     */
    public function getHand(int | null $player = null): array {
        if (!isset($this->hand) || (!isset($this->player) && is_null($player))) {
            $this->initializeGame();
        }

        return $this->hand[$player ?? $this->player];
    }

    /**
     * Reloads the current page by sending a Location header to the client, redirecting to the same page.
     * This method is used for refreshing the user interface after certain game actions or updates.
     */
    private function reloadPage(): void {
        header("Location: index.php");
    }

    /**
     * Plays a piece on the specified position on the board.
     *
     * @param string $pos   Coordinates of the board position where the piece is to be played (in the format "x,y").
     * @param string $piece The piece to be played.
     * @return void
     */
    public function play(string $pos, string $piece): void {
        $hand = $this->getHand();
        if (isset($this->board[$pos])) /** Correct */ {
            $this->setError("The board position is already in use.");
        } elseif (!isset($hand[$piece]) || $hand[$piece] <= 0) /** Correct */ {
            $this->setError("You don't have this piece available.");
        } elseif (count($this->board) > 0 && !$this->hasNeighbour($pos)) /** Correct */ {
            $this->setError("The board position has no neighboring cells.");
        } elseif ($piece != "Q" && $this->turnCounter >= 6 && (($hand["Q"] ?? 0) != 0)) /** Correct */ {
            $this->setError("The queen bee has to be played this turn.");
        } elseif (array_sum($hand) < 11 && !$this->neighboursAreSameColor($this->player, $pos)) /** Correct */ {
            $this->setError("The board position is adjacent to an opposing piece, this is not possible.");
        } else {
            $this->hand[$this->player][$piece]--;
            $this->board[$pos] = [[$this->player, $piece]];
            $this->prevMoveId = $this->databaseHandler->
                addMove($this->gameId, $piece, $pos, $this->prevMoveId, $this->getSerializedState());
            $this->turnCounter++;
            $this->player = ($this->player + 1) % 2;

            //TODO: Add check if game is over.
        }
    }

    private function move(string $fromPos, string $toPos): void {

    }

    private function pass(): void {

    }

    private function undo(): void {

    }

    /**
     * Restarts the game by resetting the board, player hands, and other relevant game state variables.
     * This method initializes a new game and updates the session with the updated state.
     */
    public function restart(): void {
        $this->board = [];
        $this->hand = [
            0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3],
            1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]
        ];
        $this->player = 0;
        $this->gameId = $this->databaseHandler->restartGame();
        $this->error = null;
        $this->prevMoveId = null;
        $this->turnCounter = 0;

        $this->updateSession();
    }

    /**
     * Initializes the game state by retrieving necessary information from the session.
     * This method should be called to set up the game state at the beginning of each request.
     */
    public function initializeGame(): void {
        $this->gameId = $_SESSION["game_id"];
        $this->player = $_SESSION["player"];
        $this->hand = $_SESSION["hand"];
        $this->board = $_SESSION["board"];
        $this->prevMoveId = $_SESSION["last_move"] ?? null;
        $this->turnCounter = $_SESSION["turn_counter"] ?? 0;
        $this->error = $_SESSION["error"] ?? null;
    }

    /**
     * Updates the session with the current game state.
     * This method should be called to persist the game state in the session after each move.
     */
    public function updateSession(): void {
        $_SESSION["game_id"] = $this->gameId;
        $_SESSION["player"] = $this->player;
        $_SESSION["hand"] = $this->hand;
        $_SESSION["board"] = $this->board;
        $_SESSION["last_move"] = $this->prevMoveId;
        $_SESSION["turn_counter"] = $this->turnCounter;
        $_SESSION["error"] = $this->error;
    }

    private function setError(string $msg): void {
        $this->error = $msg;
    }

    /**
     * Check if a position has a neighboring piece on the board.
     *
     * @param string $a     The position to check for neighbors.
     *
     * @return bool True if the position has at least one neighbor; false otherwise.
     */
    private function hasNeighbour(string $a): bool {
        foreach (array_keys($this->board) as $b) {
            if ($this->isNeighbour($a, $b)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if two given board positions are neighbors on the hive board.
     *
     * @param string $a The coordinates of the first board position (in the format "x,y").
     * @param string $b The coordinates of the second board position (in the format "x,y").
     * @return bool Returns true if the two board positions are neighbors, false otherwise.
     */
    private function isNeighbour(string $a, string $b): bool {
        [$x1, $y1] = explode(",", $a);
        [$x2, $y2] = explode(",", $b);

        return ($x1 == $x2 && abs($y1 - $y2) == 1) ||
            (($y1 == $y2 && abs($x1 - $x2) == 1)) ||
            (((int)$x1 + (int)$x2 == (int)$y1 + (int)$y2));
    }

    /**
     * Checks if the neighbors of a specified position on the board have the same color as the given player.
     *
     * @param int         $player The player's color to compare with neighbors.
     * @param string      $a      Coordinates of the specified position (in the format "x,y").
     * @param array|null  $board  The game board (optional, defaults to the class property $this->board).
     *
     * @return bool Returns true if neighbors have the same color as the player, false otherwise.
     */
    private function neighboursAreSameColor(int $player, string $a, array | null $board = null): bool {
        $board = $board ?? $this->board;
        foreach ($board as $b => $st) {
            if (!$st) {
                //No piece placed in this position
                continue;
            }

            //Check top of the stack for user
            $c = $st[count($st) - 1][0];

            if ($c != $player && $this->isNeighbour($a, $b)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Retrieves an array of all positions within the boundaries of the current game board.
     *
     * @return array An array containing all positions within the boundaries.
     */
    public function getBoundaries(): array {
        if (!isset($this->board)) {
            $this->initializeGame();
        }

        $minX = PHP_INT_MAX;
        $maxX = PHP_INT_MIN;
        $minY = PHP_INT_MAX;
        $maxY = PHP_INT_MIN;

        foreach ($this->board as $position => $values) {
            [$x, $y] = array_map('intval', explode(',', $position));

            $minX = min($minX, $x);
            $maxX = max($maxX, $x);
            $minY = min($minY, $y);
            $maxY = max($maxY, $y);
        }

        $positions = [];

        for ($i = $minX - 1; $i <= $maxX + 1; $i++) {
            for ($j = $minY - 1; $j <= $maxY + 1; $j++) {
                $positions[] = "$i,$j";
            }
        }

        return $positions;
    }


    /**
     * Returns the serialized state of the game.
     *
     * @return string The serialized state of the game, including hand, board, player,
     * previous move ID, and turn counter.
     */
    private function getSerializedState(): string {
        return serialize([$this->hand, $this->board, $this->player, $this->prevMoveId, $this->turnCounter]);
    }
}