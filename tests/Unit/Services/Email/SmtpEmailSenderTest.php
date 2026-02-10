<?php

namespace Tests\Unit\Services\Email;

use Mockery;
use Tests\TestCase;
use App\Services\EmailSenders\SmtpEmailSender;
use App\Contracts\EmailSenderInterface;

class SmtpEmailSenderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_implements_email_sender_interface(): void
    {
        $mailer = Mockery::mock(\Illuminate\Mail\Mailer::class);
        $sender = new SmtpEmailSender($mailer);
        $this->assertInstanceOf(EmailSenderInterface::class, $sender);
    }

    public function test_it_sends_email_via_mailer(): void
    {
        $mailer = Mockery::mock(\Illuminate\Mail\Mailer::class);
        $subscriber = Mockery::mock(\App\Contracts\Subscriber\Sendable::class);

        $subscriber->shouldReceive('getEmail')
            ->once()
            ->andReturn('test@example.com');

        $subscriber->shouldReceive('getName')
            ->once()
            ->andReturn('John Doe');

        $mailer->shouldReceive('send')
            ->once()
            ->with(
                Mockery::type('array'),  // view data
                Mockery::type('array'),  // variables
                Mockery::on(function ($callback) use ($subscriber) {
                    $message = Mockery::mock(\Illuminate\Mail\Message::class);
                    $message->shouldReceive('to')
                        ->with('test@example.com', 'John Doe')
                        ->once()
                        ->andReturnSelf();
                    $message->shouldReceive('subject')
                        ->with('Test Subject')
                        ->once()
                        ->andReturnSelf();

                    $callback($message);
                    return true;
                })
            );

        $sender = new SmtpEmailSender($mailer);

        $result = $sender->send($subscriber, 'Test Subject', '<p>Test Body</p>');

        $this->assertInstanceOf(\App\DTOs\SendResult::class, $result);
    }

    public function test_it_returns_send_result_with_message_id(): void
    {
        $mailer = Mockery::mock(\Illuminate\Mail\Mailer::class);
        $subscriber = Mockery::mock(\App\Contracts\Subscriber\Sendable::class);
        
        $subscriber->shouldReceive('getEmail')->andReturn('test@example.com');
        $subscriber->shouldReceive('getName')->andReturn('John');
        $mailer->shouldReceive('send')->once();
        
        $sender = new SmtpEmailSender($mailer);
        
        $result = $sender->send($subscriber, 'Subject', '<p>Body</p>');
        
        $this->assertInstanceOf(\App\DTOs\SendResult::class, $result);
        $this->assertNotEmpty($result->messageId);
        $this->assertEquals('sent', $result->status);
    }

    public function test_it_throws_exception_when_send_fails(): void
    {
        $mailer = Mockery::mock(\Illuminate\Mail\Mailer::class);
        $subscriber = Mockery::mock(\App\Contracts\Subscriber\Sendable::class);

        $subscriber->shouldReceive('getEmail')->andReturn('invalid@');
        $subscriber->shouldReceive('getName')->andReturn('Test');

        $mailer->shouldReceive('send')
            ->andThrow(new \Exception('SMTP connection failed'));

        $sender = new SmtpEmailSender($mailer);

        $this->expectException(\App\Exceptions\SendFailedException::class);
        $this->expectExceptionMessage('Failed to send email');

        $sender->send($subscriber, 'Subject', 'Body');
    }

    public function test_it_sends_batch_to_all_recipients(): void
    {
        $mailer = Mockery::mock(\Illuminate\Mail\Mailer::class);

        $subscribers = $this->createSubscribers([
            ['email' => 'alice@example.com', 'name' => 'Alice'],
            ['email' => 'bob@example.com', 'name' => 'Bob'],
        ]);

        $mailer->shouldReceive('send')->twice();

        $sender = new SmtpEmailSender($mailer);
        $result = $sender->sendBatch($subscribers, 'Batch Subject', '<p>Batch Body</p>');

        $this->assertInstanceOf(\App\DTOs\BatchResult::class, $result);
        $this->assertEquals(2, $result->totalSent);
        $this->assertEquals(0, $result->totalFailed);
        $this->assertCount(2, $result->results);
    }

    public function test_it_returns_batch_result_with_unique_message_ids(): void
    {
        $mailer = Mockery::mock(\Illuminate\Mail\Mailer::class);

        $subscribers = $this->createSubscribers([
            ['email' => 'alice@example.com', 'name' => 'Alice'],
            ['email' => 'bob@example.com', 'name' => 'Bob'],
        ]);

        $mailer->shouldReceive('send')->twice();

        $sender = new SmtpEmailSender($mailer);
        $result = $sender->sendBatch($subscribers, 'Subject', '<p>Body</p>');

        $messageIds = array_map(fn ($r) => $r->messageId, $result->results);
        $this->assertCount(2, array_unique($messageIds));
    }

    public function test_it_handles_partial_batch_failure(): void
    {
        $mailer = Mockery::mock(\Illuminate\Mail\Mailer::class);

        $subscribers = $this->createSubscribers([
            ['email' => 'alice@example.com', 'name' => 'Alice'],
            ['email' => 'failing@example.com', 'name' => 'Failing'],
        ]);

        $mailer->shouldReceive('send')
            ->once()
            ->ordered();

        $mailer->shouldReceive('send')
            ->once()
            ->ordered()
            ->andThrow(new \Exception('SMTP error'));

        $sender = new SmtpEmailSender($mailer);
        $result = $sender->sendBatch($subscribers, 'Subject', '<p>Body</p>');

        $this->assertEquals(1, $result->totalSent);
        $this->assertEquals(1, $result->totalFailed);
        $this->assertTrue($result->hasFailures());
        $this->assertCount(2, $result->results);
    }

    public function test_it_handles_all_failing_in_batch(): void
    {
        $mailer = Mockery::mock(\Illuminate\Mail\Mailer::class);

        $subscribers = $this->createSubscribers([
            ['email' => 'fail1@example.com', 'name' => 'Fail1'],
            ['email' => 'fail2@example.com', 'name' => 'Fail2'],
        ]);

        $mailer->shouldReceive('send')
            ->twice()
            ->andThrow(new \Exception('SMTP error'));

        $sender = new SmtpEmailSender($mailer);
        $result = $sender->sendBatch($subscribers, 'Subject', '<p>Body</p>');

        $this->assertEquals(0, $result->totalSent);
        $this->assertEquals(2, $result->totalFailed);
        $this->assertTrue($result->hasFailures());
    }

    public function test_it_returns_empty_batch_result_for_no_recipients(): void
    {
        $mailer = Mockery::mock(\Illuminate\Mail\Mailer::class);
        $mailer->shouldNotReceive('send');

        $sender = new SmtpEmailSender($mailer);
        $result = $sender->sendBatch([], 'Subject', '<p>Body</p>');

        $this->assertInstanceOf(\App\DTOs\BatchResult::class, $result);
        $this->assertEquals(0, $result->totalSent);
        $this->assertEquals(0, $result->totalFailed);
        $this->assertEmpty($result->results);
    }

    public function test_it_marks_failed_results_with_failed_status_in_batch(): void
    {
        $mailer = Mockery::mock(\Illuminate\Mail\Mailer::class);

        $subscribers = $this->createSubscribers([
            ['email' => 'good@example.com', 'name' => 'Good'],
            ['email' => 'bad@example.com', 'name' => 'Bad'],
        ]);

        $mailer->shouldReceive('send')
            ->once()
            ->ordered();

        $mailer->shouldReceive('send')
            ->once()
            ->ordered()
            ->andThrow(new \Exception('SMTP error'));

        $sender = new SmtpEmailSender($mailer);
        $result = $sender->sendBatch($subscribers, 'Subject', '<p>Body</p>');

        $successful = $result->getSuccessful();
        $failed = $result->getFailed();

        $this->assertCount(1, $successful);
        $this->assertCount(1, $failed);
        $this->assertEquals('sent', $successful[0]->status);
        $this->assertNotEquals('sent', $failed[0]->status);
    }

    /**
     * @param list<array{email: string, name: string}> $data
     * @return list<\App\Contracts\Subscriber\Sendable>
     */
    private function createSubscribers(array $data): array
    {
        return array_map(function (array $item) {
            $subscriber = Mockery::mock(\App\Contracts\Subscriber\Sendable::class);
            $subscriber->shouldReceive('getEmail')->andReturn($item['email']);
            $subscriber->shouldReceive('getName')->andReturn($item['name']);
            return $subscriber;
        }, $data);
    }
}
