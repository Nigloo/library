<?php

class Model
{
    protected $pdo;

    public function __construct(array $config)
    {
        try {
            if ($config['engine'] == 'mysql') {
                $this->pdo = new \PDO(
                    'mysql:dbname='.$config['database'].';host='.$config['host'],
                    $config['user'],
                    $config['password']
                );
                $this->pdo->exec('SET CHARSET UTF8');
            } else {
                $this->pdo = new \PDO(
                    'sqlite:'.$config['file']
                );
            }
        } catch (\PDOException $error) {
            throw new ModelException('Unable to connect to database');
        }
    }

    /**
     * Tries to execute a statement, throw an explicit exception on failure
     */
    protected function execute(\PDOStatement $query, array $variables = array())
    {
        if (!$query->execute($variables)) {
            $errors = $query->errorInfo();
            throw new ModelException($errors[2]);
        }

        return $query;
    }

    /**
     * Inserting a book in the database
     */
    public function insertBook($title, $author, $synopsis, $image, $copies)
    {
        $query = $this->pdo->prepare('INSERT INTO livres (titre, auteur, synopsis, image)
            VALUES (?, ?, ?, ?)');
            
        for ($i = 0; $i < $copies; $i++) {
            $this->execute($query, array($title, $author, $synopsis, $image));
        }
    }

    /**
     * Getting all the books
     */
    public function getBooks()
    {
        $query = $this->pdo->prepare('SELECT * FROM livres GROUP BY titre');

        $this->execute($query);
    
        return $query->fetchAll();
    }
    
    
    /**
     * Getting a single book
     */
    public function getBook($titre)
    {
        $query = $this->pdo->prepare('SELECT livres.* FROM livres WHERE livres.titre = ? GROUP BY livres.titre, livres.auteur, livres.synopsis, livres.image');
        $this->execute($query, array($titre));
        $book = $query->fetch();
        
        $query = $this->pdo->prepare('SELECT count(id) FROM livres WHERE livres.titre = ?');
        $this->execute($query, array($titre));
        $book['nb_total'] = $query->fetch();
        
        $query = $this->pdo->prepare('SELECT id FROM livres WHERE livres.titre = ?');
        $this->execute($query, array($titre));
        $book['ids'] = $query->fetchAll();
        
        $query = $this->pdo->prepare('SELECT count(livres.id) FROM livres WHERE livres.id NOT IN (SELECT emprunts.id FROM emprunts)');
        $this->execute($query, array($titre));
        $book['nb_left'] = $query->fetch();
        
        $query = $this->pdo->prepare('SELECT livres.id FROM livres JOIN emprunts ON livres.id = emprunts.id WHERE livres.titre = ? AND emprunts.fini');
        $this->execute($query, array($titre));
        $book['emprunt'] = $query->fetchAll();
        
        return $book;
    }
}
