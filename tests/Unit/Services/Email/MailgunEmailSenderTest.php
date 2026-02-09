<?php

namespace Tests\Unit\Services\Email;

use Mockery;
use Tests\TestCase;
use Psr\Log\LoggerInterface;

use App\DTOs\SendResult;
use App\DTOs\BatchResult;
use App\Contracts\Subscriber\Sendable;
use App\Contracts\EmailSenderInterface;
use App\Exceptions\SendFailedException;
use App\Services\EmailSenders\MailgunEmailSender;

class MailgunEmailSenderTest extends TestCase
{
    private LoggerInterface $logger;
    private MailgunEmailSender $sender;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->sender = new MailgunEmailSender($this->logger, 'mg.example.com', 'from@example.com', 'Sender');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_implements_email_sender_interface(): void
    {
        $this->assertInstanceOf(EmailSenderInterface::class, $this->sender);
    }

    /** @test */
    public function it_logs_email_details(): void
    {
        $subscriber = $this->createSubscriber('test@example.com', 'John Doe');

        $this->logger->shouldReceive('info')
            ->once()
            ->with(
                'Mailgun: Sending email',
                Mockery::on(function (array $context) {
                    return $context['to'] === 'John Doe <test@example.com>'
                        && $context['from'] === 'Sender <from@example.com>'
                        && $context['subject'] === 'Test Subject'
                        && $context['domain'] === 'mg.example.com';
                })
            );

        $result = $this->sender->send($subscriber, 'Test Subject', '<p>Test Body</p>');

        $this->assertInstanceOf(SendResult::class, $result);
    }

    /** @test */
    public function it_logs_email_without_name_when_name_is_null(): void
    {
        $subscriber = $this->createSubscriber('test@example.com', null);

        $this->logger->shouldReceive('info')
            ->once()
            ->with(
                'Mailgun: Sending email',
                Mockery::on(function (array $context) {
                    return $context['to'] === 'test@example.com';
                })
            );

        $result = $this->sender->send($subscriber, 'Subject', '<p>Body</p>');

        $this->assertInstanceOf(SendResult::class, $result);
    }

    /** @test */
    public function it_returns_send_result_with_message_id(): void
    {
        $subscriber = $this->createSubscriber('test@example.com', 'John');

        $this->logger->shouldReceive('info')->once();

        $result = $this->sender->send($subscriber, 'Subject', '<p>Body</p>');

        $this->assertInstanceOf(SendResult::class, $result);
        $this->assertNotEmpty($result->messageId);
        $this->assertEquals('sent', $result->status);
    }

    /** @test */
    public function it_throws_exception_when_logging_fails(): void
    {
        $subscriber = $this->createSubscriber('test@example.com', 'John');

        $this->logger->shouldReceive('info')
            ->once()
            ->andThrow(new \RuntimeException('Log write failed'));

        $this->expectException(SendFailedException::class);
        $this->expectExceptionMessage('Failed to send email via Mailgun');

        $this->sender->send($subscriber, 'Subject', '<p>Body</p>');
    }

    /** @test */
    public function it_sends_batch_to_all_recipients(): void
    {
        $subscribers = $this->createSubscribers([
            ['email' => 'alice@example.com', 'name' => 'Alice'],
            ['email' => 'bob@example.com', 'name' => 'Bob'],
        ]);

        $this->logger->shouldReceive('info')->twice();

        $result = $this->sender->sendBatch($subscribers, 'Batch Subject', '<p>Batch Body</p>');

        $this->assertInstanceOf(BatchResult::class, $result);
        $this->assertEquals(2, $result->totalSent);
        $this->assertEquals(0, $result->totalFailed);
        $this->assertCount(2, $result->results);
    }

    /** @test */
    public function it_returns_batch_result_with_unique_message_ids(): void
    {
        $subscribers = $this->createSubscribers([
            ['email' => 'alice@example.com', 'name' => 'Alice'],
            ['email' => 'bob@example.com', 'name' => 'Bob'],
        ]);

        $this->logger->shouldReceive('info')->twice();

        $result = $this->sender->sendBatch($subscribers, 'Subject', '<p>Body</p>');

        $messageIds = array_map(fn($r) => $r->messageId, $result->results);
        $this->assertCount(2, array_unique($messageIds));
    }

    /** @test */
    public function it_handles_partial_batch_failure(): void
    {
        $subscribers = $this->createSubscribers([
            ['email' => 'alice@example.com', 'name' => 'Alice'],
            ['email' => 'failing@example.com', 'name' => 'Failing'],
        ]);

        $this->logger->shouldReceive('info')
            ->once()
            ->ordered();

        $this->logger->shouldReceive('info')
            ->once()
            ->ordered()
            ->andThrow(new \RuntimeException('Log write failed'));

        $result = $this->sender->sendBatch($subscribers, 'Subject', '<p>Body</p>');

        $this->assertEquals(1, $result->totalSent);
        $this->assertEquals(1, $result->totalFailed);
        $this->assertTrue($result->hasFailures());
        $this->assertCount(2, $result->results);
    }

    /** @test */
    public function it_handles_all_failing_in_batch(): void
    {
        $subscribers = $this->createSubscribers([
            ['email' => 'fail1@example.com', 'name' => 'Fail1'],
            ['email' => 'fail2@example.com', 'name' => 'Fail2'],
        ]);

        $this->logger->shouldReceive('info')
            ->twice()
            ->andThrow(new \RuntimeException('Log write failed'));

        $result = $this->sender->sendBatch($subscribers, 'Subject', '<p>Body</p>');

        $this->assertEquals(0, $result->totalSent);
        $this->assertEquals(2, $result->totalFailed);
        $this->assertTrue($result->hasFailures());
    }

    /** @test */
    public function it_returns_empty_batch_result_for_no_recipients(): void
    {
        $this->logger->shouldNotReceive('info');

        $result = $this->sender->sendBatch([], 'Subject', '<p>Body</p>');

        $this->assertInstanceOf(BatchResult::class, $result);
        $this->assertEquals(0, $result->totalSent);
        $this->assertEquals(0, $result->totalFailed);
        $this->assertEmpty($result->results);
    }

    /** @test */
    public function it_marks_failed_results_with_failed_status_in_batch(): void
    {
        $subscribers = $this->createSubscribers([
            ['email' => 'good@example.com', 'name' => 'Good'],
            ['email' => 'bad@example.com', 'name' => 'Bad'],
        ]);

        $this->logger->shouldReceive('info')
            ->once()
            ->ordered();

        $this->logger->shouldReceive('info')
            ->once()
            ->ordered()
            ->andThrow(new \RuntimeException('Log write failed'));

        $result = $this->sender->sendBatch($subscribers, 'Subject', '<p>Body</p>');

        $successful = $result->getSuccessful();
        $failed = $result->getFailed();

        $this->assertCount(1, $successful);
        $this->assertCount(1, $failed);
        $this->assertEquals('sent', $successful[0]->status);
        $this->assertNotEquals('sent', $failed[0]->status);
    }

    private function createSubscriber(string $email, ?string $name): Sendable
    {
        $subscriber = Mockery::mock(Sendable::class);
        $subscriber->shouldReceive('getEmail')->andReturn($email);
        $subscriber->shouldReceive('getName')->andReturn($name);
        return $subscriber;
    }

    /**
     * @param list<array{email: string, name: string}> $data
     * @return list<Sendable>
     */
    private function createSubscribers(array $data): array
    {
        return array_map(function (array $item) {
            return $this->createSubscriber($item['email'], $item['name']);
        }, $data);
    }
}
