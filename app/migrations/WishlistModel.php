<?php

/**
 * Class WishlistModel_Migration
 *
 * A plant "wishlist" entry: something the user doesn't own yet but wants
 * to get. Deliberately separate from PlantsModel - a wishlist item is
 * not an inventory row, has no care tracking, and is owned by the user
 * who added it rather than being a shared workspace object.
 */
class WishlistModel_Migration {
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
        $this->database = new Asatru\Database\Migration('WishlistModel', $this->connection);
        $this->database->drop();
        $this->database->add('id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        $this->database->add('user INT NOT NULL');
        $this->database->add('name VARCHAR(255) NOT NULL');
        $this->database->add('species VARCHAR(512) NULL');
        $this->database->add('cultivar VARCHAR(512) NULL');
        $this->database->add('notes TEXT NULL');
        $this->database->add('priority VARCHAR(32) NOT NULL DEFAULT \'would_like\'');
        $this->database->add('photo VARCHAR(255) NULL');
        $this->database->add('location INT NULL');
        $this->database->add('source_url VARCHAR(1024) NULL');
        $this->database->add('price DECIMAL(10, 2) NULL');
        $this->database->add('best_time_note VARCHAR(512) NULL');
        $this->database->add('updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->database->add('created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->database->create();
    }

    /**
     * Called when the table shall be removed
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
