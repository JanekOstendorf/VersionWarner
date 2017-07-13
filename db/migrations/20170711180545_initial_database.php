<?php

use Phinx\Migration\AbstractMigration;

class InitialDatabase extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $tableRecipients = $this->table('recipients')
            ->addColumn('name', 'string')
            ->addColumn('email', 'string')
            ->addColumn('enabled', 'boolean');
        $tableRecipients->create();

        $tableChecks = $this->table('checks')
            ->addColumn('name', 'string')
            ->addColumn('enabled', 'boolean')
            ->addColumn('title', 'string')
            ->addColumn('provider', 'string');
        $tableChecks->create();

        $tableChecksArguments = $this->table('checks_arguments')
            ->addColumn('check_id', 'integer')
            ->addColumn('key', 'string')
            ->addColumn('value', 'string')
            ->addForeignKey('check_id', 'checks', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ]);
        $tableChecksArguments->create();

        $tableCheckRecipients = $this->table('check_recipients')
            ->addColumn('recipient_id', 'integer')
            ->addColumn('check_id', 'integer')
            ->addForeignKey('recipient_id', 'recipients', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE'
            ])
            ->addForeignKey('check_id', 'checks', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE'
            ]);
        $tableCheckRecipients->create();
    }
}
