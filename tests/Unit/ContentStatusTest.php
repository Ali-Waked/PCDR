<?php

namespace Tests\Unit;

use App\Enums\ContentStatus;
use Tests\TestCase;

class ContentStatusTest extends TestCase
{
    public function test_all_required_workflow_states_are_defined(): void
    {
        $expected = ['draft', 'pending_review', 'approved', 'published', 'archived'];

        $this->assertSame($expected, array_column(ContentStatus::cases(), 'value'));
        $this->assertSame($expected, config('workflows.states'));
    }

    public function test_workflow_transitions_follow_the_defined_contract(): void
    {
        $this->assertTrue(ContentStatus::Draft->canTransitionTo(ContentStatus::PendingReview));
        $this->assertFalse(ContentStatus::Draft->canTransitionTo(ContentStatus::Published));
        $this->assertTrue(ContentStatus::PendingReview->canTransitionTo(ContentStatus::Approved));
        $this->assertTrue(ContentStatus::Approved->canTransitionTo(ContentStatus::Published));
        $this->assertFalse(ContentStatus::Published->canTransitionTo(ContentStatus::Draft));
        $this->assertTrue(ContentStatus::Published->canTransitionTo(ContentStatus::Archived));
        $this->assertTrue(ContentStatus::Archived->canTransitionTo(ContentStatus::Draft));
    }
}
