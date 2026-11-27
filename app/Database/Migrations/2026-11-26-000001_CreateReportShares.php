<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReportShares extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'token_hash'          => ['type' => 'CHAR', 'constraint' => 64],
            'report_id'           => ['type' => 'INT', 'unsigned' => true],
            'created_by'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_by_username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'pin_hash'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'expires_at'          => ['type' => 'DATETIME'],
            'max_views'           => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'view_count'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'failed_attempts'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'revoked_at'          => ['type' => 'DATETIME', 'null' => true],
            'last_accessed_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addKey('report_id');
        $this->forge->createTable('report_shares');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'share_id'   => ['type' => 'INT', 'unsigned' => true],
            'event'      => ['type' => 'VARCHAR', 'constraint' => 30],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('share_id');
        $this->forge->createTable('report_share_access');
    }

    public function down()
    {
        $this->forge->dropTable('report_share_access', true);
        $this->forge->dropTable('report_shares', true);
    }
}
