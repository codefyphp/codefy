<?php

/**
 * CodefyPHP
 *
 * @link       https://github.com/codefyphp/codefy
 * @copyright  2022
 * @author     Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      0.1.0
 */

declare(strict_types=1);

namespace Codefy\Tests;

use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Codefy\Domain\Aggregate\AggregateRepository;
use Qubus\Exception\Data\TypeException;

class CreatePostCommandHandler implements CommandHandler
{
    public function __construct(public readonly AggregateRepository $aggregateRepository)
    {
    }

    /**
     * @throws TypeException|TitleWasNullException
     */
    public function handle(Command $command)
    {
        $post = Post::createPostWithoutTap(
            postId: new PostId($command->postId()),
            title: new Title($command->title()),
            content: new Content($command->content())
        );

        $this->aggregateRepository->saveAggregateRoot(aggregate: $post);
    }
}
