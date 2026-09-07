<?php

/**
 * Class LocationLogPlantModel_Migration
 */
class LocationLogPlantModel_Migration {
    private $database = null;
    private $connection = null;

    /**
     * Store the PDO connection handle
     *
     * @param \PDO $pdo The PDO connection handle
     * @return void
     */
    public function __construct($pdo)
    {
        $this->connection = $pdo;
    }

    /**
     * Called when the table shall be created or modified
     *
     * @return void
     */
    public function up()
    {
        $this->database = new Asatru\Database\Migration('LocationLogPlantModel', $this->connection);
        $this->database->drop();
        $this->database->add('id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        $this->database->add('log_entry INT NOT NULL');
        $this->database->add('plant INT NOT NULL');
        $this->database->create();
    }

    /**
     * Called when the table shall be dropped
     *
     * @return void
     */
    public function down()
    {
        if ($this->database)
            $this->database->drop();
    }
}
