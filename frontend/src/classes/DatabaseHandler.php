<?php

namespace Classes;

use Exception;
use mysqli;
class DatabaseHandler
{
    private mysqli $conn;
    private string $hostname = 'localhost';
    private string $username = 'root';
    private string $password = '';
    private string $database = 'hive';

    /**
     * Executes a move action in the Hive game.
     *
     * @param int    $gameId The ID of the game.
     * @param string $fromPos The position from which the move is made.
     * @param string $toPos The position to which the move is made.
     * @param int    $prevId The ID of the previous move.
     * @param string $state The state of the game.
     *
     * @return int The ID of the newly inserted move.
     */
    public function doMove(int $gameId, string $fromPos, string $toPos, int $prevId, string $state): int {
        return $this->doAction($gameId, "move", $fromPos, $toPos, $prevId, $state);
    }

    /**
     * Adds a play move action in the Hive game.
     *
     * @param int    $gameId The ID of the game.
     * @param string $piece  The piece being played.
     * @param string $toPos  The position to which the piece is played.
     * @param int    $prevId The ID of the previous move.
     * @param string $state  The state of the game.
     *
     * @return int The ID of the newly inserted move.
     */
    public function addMove(int $gameId, string $piece, string $toPos, int $prevId, string $state): int {
        return $this->doAction($gameId, "play", $piece, $toPos, $prevId, $state);
    }

    public function undoMove(): void {
        throw new Exception("Not implemented yet! Contained a bug. So its removed in restructure.");
    }

    /**
     * Executes a pass action in the Hive game.
     *
     * @param int $gameId The ID of the game.
     * @param int    $prevId The ID of the previous move.
     * @param string $state The state of the game.
     *
     * @return int The ID of the newly inserted move.
     */
    public function doPass(int $gameId, int $prevId, string $state): int {
        return $this->doAction($gameId, "pass", null, null, $prevId, $state);
    }

    public function restartGame(): int {
        $db = $this->getConnection();
        $stmt = $db->prepare("INSERT INTO games;");
        $stmt->execute();

        return $db->insert_id;
    }

    /**
     * Executes a generic action in the Hive game.
     *
     * @param int         $gameId The ID of the game.
     * @param string      $action The type of action (e.g., "move", "pass").
     * @param string|null $fromPos The position from which the move is made (null for pass action).
     * @param string|null $toPos The position to which the move is made (null for pass action).
     * @param int         $prevId The ID of the previous move.
     * @param string      $state The state of the game.
     *
     * @return int The ID of the newly inserted move.
     */
    private function doAction(
        int $gameId,
        string $action,
        string | null $fromPos,
        string | null $toPos,
        int $prevId,
        string $state
    ): int {
        $db = $this->getConnection();
        $cmd = "INSERT INTO moves (game_id, type, move_from, move_to, previous_id, state) VALUES (?, ?, ?, ?, ?, ?);";
        $stmt = $db->prepare($cmd);
        $stmt->bind_param("isssis", $gameId, $action, $fromPos, $toPos, $prevId, $state);
        $stmt->execute();

        return $db->insert_id;
    }

    /**
     * Gets the database connection for the Hive game.
     *
     * @return mysqli The MySQLi database connection.
     */
    private function getConnection(): mysqli {
        if (!isset($this->conn)) {
            $conn = new mysqli($this->hostname, $this->username, $this->password, $this->database);

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $this->conn = $conn;
        }
        return $this->conn;
    }
}