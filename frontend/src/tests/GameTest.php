<?php

use Classes\DatabaseHandler;
use Classes\Game;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\Stub;

class GameTest extends TestCase
{
    private Game $hive;
    private Stub $dbStub;

    protected function setUp(): void {
        $dbStub = self::createStub(DatabaseHandler::class);
        $dbStub->method("restartGame")->willReturn(0);
        $this->dbStub = $dbStub;

        $this->hive = new Game($this->dbStub);
    }

    public function test_GetAvailablePieces_StartOfGame_ShouldReturnAllPieces(): void {
        # Arrange
        $this->hive->restart();

        # Act
        $availablePieces = $this->hive->getAvailablePieces();

        # Assert
        self::assertEquals(["A", "B", "G", "Q", "S"], $availablePieces);
    }

    public function test_GetAvailablePieces_AfterPlacingAllSpiders_ShouldReturnAllPiecesButSpider(): void {
        # Arrange
        $this->hive->restart();

        # Act
        //Player 1 plays first turn.
        $this->hive->play("0,0", "S");
        //Player 2 plays first turn.
        $this->hive->play("0,1", "S");
        //Player 1 plays second turn.
        $this->hive->play("-1,0", "S");
        //Player 2 plays second turn.
        $this->hive->play("0,2", "S");
        //Get all available pieces from player 1.
        $playerOnePieces = $this->hive->getAvailablePieces();
        $this->hive->play("-1,-1", "Q");
        //Get all available pieces from player 1.
        $playerTwoPieces = $this->hive->getAvailablePieces();

        # Assert
        self::assertEquals(["A", "B", "G", "Q"], $playerOnePieces);
        self::assertEquals(["A", "B", "G", "Q"], $playerTwoPieces);
    }

    public function test_GetValidMoves_FirstTurnPlayerOne_ShouldReturnZeroZeroOnly(): void {
        # Arrange
        $this->hive->restart();

        # Act
        $validMoves = $this->hive->getValidPlayMoves();

        # Assert
        self::assertEquals(["0,0"], $validMoves);
    }

    public function test_GetValidMoves_FirstTurnPlayerTwo_ShouldReturnPositionsAroundZeroZero(): void {
        # Arrange
        $this->hive->restart();

        # Act
        $this->hive->play("0,0", "S");
        $this->hive->updateSession();
        $validMoves = $this->hive->getValidPlayMoves();

        # Assert
        self::assertEquals(["0,1", "0,-1", "1,0", "-1,0", "-1,1", "1,-1"], $validMoves);
    }

    public function test_GetOccupiedPositions_NoPlaysDoneByPlayer_ShouldReturnEmptyArray(): void {
        # Arrange
        $this->hive->restart();

        # Act
        $positionPlayerOne = $this->hive->getOccupiedPositions();
        $this->hive->play("0,0", "S");
        $this->hive->updateSession();
        $positionPlayerTwo = $this->hive->getOccupiedPositions();

        # Assert
        self::assertEquals([], $positionPlayerOne);
        self::assertEquals([], $positionPlayerTwo);
    }

    public function test_GetOccupiedPositions_AfterMultiplePlays_ShouldReturnOwnedPositionsOnly(): void {
        # Arrange
        $this->hive->restart();

        # Act
        //Player 1 plays first turn.
        $this->hive->play("0,0", "S");
        //Player 2 plays first turn.
        $this->hive->play("0,1", "S");
        //Player 1 plays second turn.
        $this->hive->play("-1,0", "S");
        //Player 2 plays second turn.
        $this->hive->play("0,2", "S");
        $this->hive->updateSession();
        $positionPlayerOne = $this->hive->getOccupiedPositions();

        $this->hive->play("-1,-1", "Q");
        $this->hive->updateSession();
        $positionPlayerTwo = $this->hive->getOccupiedPositions();

        # Assert
        self::assertEquals(["0,0", "-1,0"], $positionPlayerOne);
        self::assertEquals(["0,1", "0,2"], $positionPlayerTwo);
    }
}