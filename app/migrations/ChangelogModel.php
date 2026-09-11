<?php

/**
 * Class ChangelogModel_Migration
 *
 * A short admin-curated history of notable changes, shown on its own
 * page linked from the Feature Request board. Deliberately its own
 * table rather than piggybacking on FeatureRequestModel's "added"
 * status - a changelog entry has no requester, no votes, and isn't
 * part of the community board.
 */
class ChangelogModel_Migration {
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
        $this->database = new Asatru\Database\Migration('ChangelogModel', $this->connection);
        $this->database->drop();
        $this->database->add('id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        $this->database->add('title VARCHAR(255) NOT NULL');
        $this->database->add('description TEXT NULL');
        $this->database->add('entry_date DATE NOT NULL');
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
        if ($this->database) {
            $this->database->drop();
        }
    }
}
