<?php

namespace App\Transformers\Types;

use App\Jobs\EmbedJob;
use App\LLMModels\OpenAi\EmbeddingsResponseDto;
use App\Models\Document;
use App\Models\Transformer;
use App\Transformers\BaseTransformer;
use Facades\App\LLMModels\OpenAi\ClientWrapper;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

class EmbedTransformer extends BaseTransformer
{
    public function handle(Transformer $transformer): Document
    {

        $batch = Bus::batch([])
            ->then(function (Batch $batch)   {
                logger("All batch jobs done for document " . $this->document->id);
            })
            ->name('Batch for Document Transformation ' . $this->document->id);


        foreach ($this->document->document_chunks as $chunk) {
            $batch->add(
                new EmbedJob($chunk)
            );
        }

        $batch->dispatch();

        return $this->document;
    }
}
