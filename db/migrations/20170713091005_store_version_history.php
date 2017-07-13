<?php

use Phinx\Migration\AbstractMigration;

class StoreVersionHistory extends AbstractMigration
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
        $this->table('versions')
            ->addColumn('check_id', 'integer')
            ->addForeignKey('check_id', 'checks', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE'
            ])
            ->addColumn('version', 'string')
            ->addColumn('first_seen', 'datetime')
            ->addColumn('last_checked', 'datetime')
            ->create();
    }
}
