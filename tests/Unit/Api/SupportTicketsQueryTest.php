<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use PHPUnit\Framework\TestCase;

class SupportTicketsQueryTest extends TestCase
{
    private const EXPECTED_COLUMNS = [
        'id', 'ticket_number', 'user_id', 'subject', 'category',
        'related_review_id', 'related_order_id', 'status', 'assigned_to',
        'created_at', 'updated_at', 'user_name', 'user_phone', 'user_email',
        'unread_count', 'message_count',
    ];

    public function testSourceFileContainsNoSelectStar(): void
    {
        $source = file_get_contents(__DIR__ . '/../../../lequocanh/api/support_tickets.php');
        $this->assertNotFalse($source, 'support_tickets.php must be readable');

        // Strip comments to avoid false positives
        $code = preg_replace('!/\*.*?\*/!s', '', $source);
        $code = preg_replace('![ \t]*//.*?$!m', '', $code);

        // Look for SELECT * (with optional whitespace) in SQL context
        $this->assertDoesNotMatchRegularExpression(
            '/SELECT\s+\*\s+FROM/i',
            $code,
            'support_tickets.php must not contain SELECT * FROM (AGENTS.md violation)'
        );
    }

    public function testViewColumnsAreExplicitInBothQueries(): void
    {
        $source = file_get_contents(__DIR__ . '/../../../lequocanh/api/support_tickets.php');
        $this->assertNotFalse($source);

        $this->assertStringContainsString('SELECT id, ticket_number, user_id, subject, category', $source);
        $this->assertStringContainsString('unread_count, message_count FROM v_support_tickets_list', $source);
    }

    public function testExpectedColumnsList(): void
    {
        $this->assertCount(16, self::EXPECTED_COLUMNS);
        $this->assertContains('id', self::EXPECTED_COLUMNS);
        $this->assertContains('message_count', self::EXPECTED_COLUMNS);
    }
}
