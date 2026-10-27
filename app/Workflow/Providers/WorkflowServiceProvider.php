<?php

namespace App\Workflow\Providers;

use App\Credentials\CredentialResolver;
use App\Credentials\Registry\CredentialRegistry;
use App\Credentials\Types\BasicAuthCredential;
use App\Credentials\Types\GeminiCredential;
use App\Credentials\Types\GmailCredential;
use App\Credentials\Types\HttpBearerCredential;
use App\Credentials\Types\TelegramCredential;
use App\Workflow\Nodes\Actions\GeminiNode;
use App\Workflow\Nodes\Actions\GmailNode;
use App\Workflow\Nodes\Actions\HttpRequestNode;
use App\Workflow\Nodes\Actions\TelegramNode;
use App\Workflow\Nodes\Ai\AiAgentNode;
use App\Workflow\Nodes\Ai\CalculatorToolNode;
use App\Workflow\Nodes\Ai\DateTimeToolNode;
use App\Workflow\Nodes\Ai\GeminiChatModelNode;
use App\Workflow\Nodes\Ai\HttpToolNode;
use App\Workflow\Nodes\Ai\SimpleMemoryNode;
use App\Workflow\Nodes\Logic\ConditionNode;
use App\Workflow\Nodes\Logic\DelayNode;
use App\Workflow\Nodes\Triggers\ChatTriggerNode;
use App\Workflow\Nodes\Triggers\GmailTriggerNode;
use App\Workflow\Nodes\Triggers\ManualTriggerNode;
use App\Workflow\Nodes\Triggers\TelegramTriggerNode;
use App\Workflow\Registry\NodeRegistry;
use App\Workflow\Runner\WorkflowRunner;
use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NodeRegistry::class, function () {
            return new NodeRegistry([
                ManualTriggerNode::class,
                ChatTriggerNode::class,
                TelegramTriggerNode::class,
                GmailTriggerNode::class,
                ConditionNode::class,
                DelayNode::class,
                HttpRequestNode::class,
                GeminiNode::class,
                TelegramNode::class,
                GmailNode::class,
                AiAgentNode::class,
                GeminiChatModelNode::class,
                SimpleMemoryNode::class,
                HttpToolNode::class,
                CalculatorToolNode::class,
                DateTimeToolNode::class,
            ]);
        });

        $this->app->singleton(WorkflowRunner::class);

        $this->app->singleton(CredentialRegistry::class, function () {
            return new CredentialRegistry([
                GeminiCredential::class,
                HttpBearerCredential::class,
                BasicAuthCredential::class,
                TelegramCredential::class,
                GmailCredential::class,
            ]);
        });

        $this->app->singleton(CredentialResolver::class);
    }

    public function boot(): void
    {
        //
    }
}
