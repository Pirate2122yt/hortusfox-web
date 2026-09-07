<?php

/**
 * Class PlantLogModel_Migration
 */
class PlantLogModel_Migration {
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
        $this->database = new Asatru\Database\Migration('PlantLogModel', $this->connection);
        $this->database->drop();
        $this->database->add('id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        $this->database->add('plant INT NOT NULL');
        $this->database->add('title VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->database->add('content TEXT NOT NULL');
        $this->database->add('tags VARCHAR(512) NOT NULL DEFAULT \'\'');
        $this->database->add('is_system TINYINT(1) NOT NULL DEFAULT 0');
        $this->database->add('entry_date DATE NULL');
        $this->database->add('updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->database->add('created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
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