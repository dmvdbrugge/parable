<?php

namespace Parable\Tests\Components\ORM;

class Base extends \Parable\Tests\Base
{
    /** @var \Parable\ORM\Database */
    protected $database;

    /** @var \Parable\Filesystem\Path */
    protected $path;

    protected function setUp()
    {
        parent::setUp();

        $this->skipIfSqliteNotAvailable();

        $this->database = \Parable\DI\Container::get(\Parable\ORM\Database::class);
        $this->path     = \Parable\DI\Container::get(\Parable\Filesystem\Path::class);

        $this->database->setType(\Parable\ORM\Database::TYPE_SQLITE);
        $this->database->setLocation(\Parable\ORM\Database::LOCATION_SQLITE_MEMORY);

        $sql = file_get_contents($this->path->getDir('tests/db/test-setup.sql'));
        $this->database->getInstance()->exec($sql);
    }

    protected function skipIfSqliteNotAvailable()
    {
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('sqlite3 is not available');
        }
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->skipIfSqliteNotAvailable();

        $sql = file_get_contents($this->path->getDir('tests/db/test-teardown.sql'));
        $this->database->getInstance()->exec($sql);
    }
}