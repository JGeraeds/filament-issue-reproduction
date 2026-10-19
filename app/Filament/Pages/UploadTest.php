<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class UploadTest extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.upload-test';

    public ?array $data = [
        'upload' => null,
        'uploads_2' => null,
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                FileUpload::make('upload')
                    ->label('Uploads')
                    ->aboveContent('This field shows the issue regarding deletion')
                    ->disk('local')
                    ->directory('uploads')
                    ->preserveFilenames()
                    ->acceptedFileTypes([
                        'image/*',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->multiple()
                    ->previewable(false)
                    ->formatStateUsing(function (): array {
                        $resp = [];
                        foreach (Storage::disk('local')->files('uploads') as $file) {
                            $resp[] = $file;
                        }

                        return $resp;
                    })
                    ->deleteUploadedFileUsing(function (string $file) {
                        if (! Storage::disk('local')->exists($file)) {
                            return [];
                        }

                        return Storage::disk('local')->delete($file);
                    }),

                FileUpload::make('uploads_2')
                    ->label('Uploads 2 preview')
                    ->aboveContent('This field shows the issue regarding naming')
                    ->disk('local')
                    ->directory('uploads')
                    ->preserveFilenames()
                    ->acceptedFileTypes([
                        'image/*',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->formatStateUsing(function (): array {
                        $resp = [];
                        foreach (Storage::disk('local')->files('uploads') as $file) {
                            $resp[] = $file;
                        }

                        return $resp;
                    })
                    ->getUploadedFileUsing(function (string $file): ?array {
                        $disk = Storage::disk('local');
                        if (! $disk->exists($file)) return null;

                        $encoded = implode('/', array_map('rawurlencode', explode('/', $file)));

                        return [
                            'name' => basename($file),
                            'size' => $disk->size($file),
                            'type' => $disk->mimeType($file),
                            'url'  => $disk->url($encoded),
                        ];
                    })
                    ->deleteUploadedFileUsing(function (string $file) {
                        if (! Storage::disk('local')->exists($file)) {
                            return [];
                        }

                        return Storage::disk('local')->delete($file);
                    }),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // The uploaded file path is available in:
        // $data['attachment']

        Notification::make()
            ->title('Form submitted successfully')
            ->success()
            ->send();
    }
}
