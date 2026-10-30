<?php

namespace App\Workflow\Nodes\Actions\Gemini;

use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\Enums\NodeCategory;

final class GeminiSchema
{
    public static function make(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.gemini',
            name: 'Google Gemini',
            category: NodeCategory::Ai,
            description: 'ارسال پرامپت، تولید و ویرایش تصویر، تحلیل صدا، ویدیو، اسناد، مخزن فایل‌ها و مدل‌های Gemini.',
            icon: 'brand:gemini',
            group: 'gemini',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type' => 'credential',
                    'credential_type' => 'gemini',
                    'label' => 'اعتبارنامه Gemini',
                    'required' => true,
                ],
                'resource' => [
                    'type' => 'select',
                    'label' => 'بخش (Resource)',
                    'options' => [
                        ['value' => 'text', 'label' => 'Text (متن و چت)'],
                        ['value' => 'image', 'label' => 'Image (تصویر)'],
                        ['value' => 'audio', 'label' => 'Audio (صدا و رونوشت)'],
                        ['value' => 'document', 'label' => 'Document (سند و فایل متنی)'],
                        ['value' => 'file_search', 'label' => 'File Search (مخزن جستجوی فایل)'],
                        ['value' => 'media_file', 'label' => 'Media File (آپلود مستقیم مدیا)'],
                        ['value' => 'video', 'label' => 'Video (ویدیو)'],
                    ],
                    'default' => 'text',
                    'required' => true,
                ],
                'operation' => [
                    'type' => 'select',
                    'label' => 'عملیات (Operation)',
                    'options' => [
                        ['value' => 'message_model', 'label' => 'Message a Model (ارسال پیام به مدل)'],
                    ],
                    'default' => 'message_model',
                    'required' => true,
                    'depends_on' => ['resource' => 'text'],
                ],
                'operation_image' => [
                    'type' => 'select',
                    'label' => 'عملیات تصویر',
                    'options' => [
                        ['value' => 'analyze_image', 'label' => 'Analyze an Image (تحلیل و پرسش از تصویر)'],
                        ['value' => 'generate_image', 'label' => 'Generate an Image (تولید تصویر با Imagen 3)'],
                        ['value' => 'edit_image', 'label' => 'Edit an Image (ویرایش و تغییر تصویر)'],
                    ],
                    'default' => 'analyze_image',
                    'required' => true,
                    'depends_on' => ['resource' => 'image'],
                ],
                'operation_audio' => [
                    'type' => 'select',
                    'label' => 'عملیات صدا',
                    'options' => [
                        ['value' => 'analyze_audio', 'label' => 'Analyze Audio (تحلیل فایل صوتی)'],
                        ['value' => 'transcribe_audio', 'label' => 'Transcribe a Recording (تبدیل گفتار به متن)'],
                    ],
                    'default' => 'analyze_audio',
                    'required' => true,
                    'depends_on' => ['resource' => 'audio'],
                ],
                'operation_document' => [
                    'type' => 'select',
                    'label' => 'عملیات اسناد',
                    'options' => [
                        ['value' => 'analyze_document', 'label' => 'Analyze Document (تحلیل سند PDF و متنی)'],
                    ],
                    'default' => 'analyze_document',
                    'required' => true,
                    'depends_on' => ['resource' => 'document'],
                ],
                'operation_file_search' => [
                    'type' => 'select',
                    'label' => 'عملیات مخزن فایل',
                    'options' => [
                        ['value' => 'create_store', 'label' => 'Create a File Search store (ایجاد مخزن جدید)'],
                        ['value' => 'list_stores', 'label' => 'List all File Search stores (لیست مخازن)'],
                        ['value' => 'upload_file_to_store', 'label' => 'Upload a file to a File Search store (افزودن داده به مخزن)'],
                        ['value' => 'delete_store', 'label' => 'Delete a File Search store (حذف مخزن)']
                    ],
                    'default' => 'list_stores',
                    'required' => true,
                    'depends_on' => ['resource' => 'file_search'],
                ],
                'operation_media_file' => [
                    'type' => 'select',
                    'label' => 'عملیات مدیا',
                    'options' => [
                        ['value' => 'upload_file', 'label' => 'Upload a media file (آپلود فایل در Files API)'],
                    ],
                    'default' => 'upload_file',
                    'required' => true,
                    'depends_on' => ['resource' => 'media_file'],
                ],
                'operation_video' => [
                    'type' => 'select',
                    'label' => 'عملیات ویدیو',
                    'options' => [
                        ['value' => 'analyze_video', 'label' => 'Analyze Video (تحلیل و بررسی ویدیو)'],
                        ['value' => 'generate_video', 'label' => 'Generate a Video (تولید پرامپت استوری‌بورد ویدیو)'],
                        ['value' => 'download_video', 'label' => 'Download a Video (دانلود محتوای ویدیویی)']
                    ],
                    'default' => 'analyze_video',
                    'required' => true,
                    'depends_on' => ['resource' => 'video'],
                ],

                'model' => [
                    'type' => 'model_select',
                    'label' => 'مدل هوش مصنوعی',
                    'required' => true,
                    'depends_on' => ['resource' => ['text', 'document']],
                ],
                'model_multimodal' => [
                    'type' => 'model_select',
                    'label' => 'مدل پردازش مدیا',
                    'required' => true,
                    'depends_on' => ['resource' => ['image', 'audio', 'video']],
                ],

                'prompt' => [
                    'type' => 'textarea',
                    'label' => 'پرامپت ورودی (Prompt)',
                    'required' => true,
                    'placeholder' => 'متن یا پرسش خود را وارد کنید؛ مثال: {{ input.chatInput }}',
                    'depends_on' => ['resource' => ['text', 'image', 'audio', 'document', 'video']],
                ],
                'system_instruction' => [
                    'type' => 'textarea',
                    'label' => 'دستورالعمل سیستمی (System Instruction)',
                    'placeholder' => 'مثال: شما یک دستیار دقیق هستید و پاسخ‌ها را به زبان فارسی ارائه می‌دهید.',
                    'advanced' => true,
                    'depends_on' => ['resource' => ['text', 'document']],
                ],
                'temperature' => [
                    'type' => 'number',
                    'label' => 'میزان خلاقیت (Temperature)',
                    'default' => 0.7,
                    'min' => 0,
                    'max' => 2,
                    'advanced' => true,
                    'depends_on' => ['resource' => ['text', 'image', 'audio', 'document']],
                ],

                'image_input' => [
                    'type' => 'text',
                    'label' => 'آدرس یا داده تصویر (Image URL / Base64 / File URI)',
                    'required' => true,
                    'placeholder' => 'https://example.com/image.jpg یا {{ input.photo }}',
                    'depends_on' => ['resource' => 'image'],
                ],
                'aspect_ratio' => [
                    'type' => 'select',
                    'label' => 'نسبت ابعاد تصویر (Aspect Ratio)',
                    'options' => [
                        ['value' => '1:1', 'label' => '1:1 (مربع)'],
                        ['value' => '16:9', 'label' => '16:9 (عریض افقی)'],
                        ['value' => '9:16', 'label' => '9:16 (عمودی استوری)'],
                        ['value' => '4:3', 'label' => '4:3 (استاندارد)'],
                        ['value' => '3:4', 'label' => '3:4 (پرتره)'],
                    ],
                    'default' => '1:1',
                    'depends_on' => ['resource' => 'image'],
                ],
                'number_of_images' => [
                    'type' => 'number',
                    'label' => 'تعداد تصاویر تولیدی',
                    'default' => 1,
                    'min' => 1,
                    'max' => 4,
                    'integer' => true,
                    'advanced' => true,
                    'depends_on' => ['resource' => 'image'],
                ],
                'output_format' => [
                    'type' => 'select',
                    'label' => 'فرمت خروجی تصویر',
                    'options' => [
                        ['value' => 'image/jpeg', 'label' => 'JPEG'],
                        ['value' => 'image/png', 'label' => 'PNG'],
                    ],
                    'default' => 'image/jpeg',
                    'advanced' => true,
                    'depends_on' => ['resource' => 'image'],
                ],

                'audio_input' => [
                    'type' => 'text',
                    'label' => 'آدرس فایل صوتی (Audio URL / Base64)',
                    'required' => true,
                    'placeholder' => 'https://example.com/voice.mp3 یا {{ input.file_url }}',
                    'depends_on' => ['resource' => 'audio'],
                ],
                'audio_mime_type' => [
                    'type' => 'select',
                    'label' => 'نوع فایل صوتی (MIME Type)',
                    'options' => [
                        ['value' => 'audio/mp3', 'label' => 'MP3 (audio/mp3)'],
                        ['value' => 'audio/wav', 'label' => 'WAV (audio/wav)'],
                        ['value' => 'audio/ogg', 'label' => 'OGG (audio/ogg)'],
                        ['value' => 'audio/aac', 'label' => 'AAC (audio/aac)'],
                        ['value' => 'audio/m4a', 'label' => 'M4A (audio/m4a)'],
                    ],
                    'default' => 'audio/mp3',
                    'depends_on' => ['resource' => 'audio'],
                ],

                'document_input' => [
                    'type' => 'text',
                    'label' => 'آدرس سند یا فایل متنی (Document URL / Base64)',
                    'required' => true,
                    'placeholder' => 'https://example.com/report.pdf یا داده رشته‌ای',
                    'depends_on' => ['resource' => 'document'],
                ],
                'document_mime_type' => [
                    'type' => 'select',
                    'label' => 'نوع سند (MIME Type)',
                    'options' => [
                        ['value' => 'application/pdf', 'label' => 'PDF (application/pdf)'],
                        ['value' => 'text/plain', 'label' => 'متن ساده (text/plain)'],
                        ['value' => 'text/html', 'label' => 'کد HTML (text/html)'],
                        ['value' => 'text/csv', 'label' => 'فایل CSV (text/csv)'],
                        ['value' => 'text/markdown', 'label' => 'مارک‌داون (text/markdown)'],
                    ],
                    'default' => 'application/pdf',
                    'depends_on' => ['resource' => 'document'],
                ],

                'store_display_name' => [
                    'type' => 'text',
                    'label' => 'نام نمایشی مخزن (Store Display Name)',
                    'required' => true,
                    'placeholder' => 'پایگاه دانش پشتیبانی مشتریان',
                    'depends_on' => ['resource' => 'file_search'],
                ],
                'store_name' => [
                    'type' => 'text',
                    'label' => 'شناسه مخزن (Store Name / Corpus ID)',
                    'required' => true,
                    'placeholder' => 'corpora/my-kb-123',
                    'depends_on' => ['resource' => 'file_search'],
                ],
                'document_title' => [
                    'type' => 'text',
                    'label' => 'عنوان سند در مخزن',
                    'required' => true,
                    'placeholder' => 'راهنمای ثبت‌نام',
                    'depends_on' => ['resource' => 'file_search'],
                ],
                'file_content' => [
                    'type' => 'textarea',
                    'label' => 'متن محتوا برای ایندکس و جستجو',
                    'required' => true,
                    'placeholder' => 'متن دانشنامه یا محتوای مورد نظر برای جستجوی معنایی',
                    'depends_on' => ['resource' => 'file_search'],
                ],

                'media_source' => [
                    'type' => 'text',
                    'label' => 'منبع فایل مدیا (URL یا Base64)',
                    'required' => true,
                    'placeholder' => 'https://example.com/media.mp4',
                    'depends_on' => ['resource' => 'media_file'],
                ],
                'media_mime_type' => [
                    'type' => 'text',
                    'label' => 'نوع MIME فایل مدیا',
                    'required' => true,
                    'placeholder' => 'video/mp4 یا image/png یا audio/mp3',
                    'default' => 'image/jpeg',
                    'depends_on' => ['resource' => 'media_file'],
                ],
                'media_display_name' => [
                    'type' => 'text',
                    'label' => 'نام نمایشی فایل',
                    'placeholder' => 'uploaded_asset_1',
                    'depends_on' => ['resource' => 'media_file'],
                ],

                'video_input' => [
                    'type' => 'text',
                    'label' => 'آدرس ویدیو (Video URL یا File URI)',
                    'required' => true,
                    'placeholder' => 'https://example.com/video.mp4 یا files/abc123xyz',
                    'depends_on' => ['resource' => 'video'],
                ],
                'video_mime_type' => [
                    'type' => 'select',
                    'label' => 'فرمت ویدیویی (MIME Type)',
                    'options' => [
                        ['value' => 'video/mp4', 'label' => 'MP4 (video/mp4)'],
                        ['value' => 'video/mov', 'label' => 'MOV (video/quicktime)'],
                        ['value' => 'video/webm', 'label' => 'WEBM (video/webm)'],
                    ],
                    'default' => 'video/mp4',
                    'depends_on' => ['resource' => 'video'],
                ],
            ],
            presets: [
                [
                    'key' => 'message_model',
                    'name' => 'Message a model',
                    'description' => 'ارسال پرامپت متنی و دریافت پاسخ از مدل‌های Gemini.',
                    'config' => ['resource' => 'text', 'operation' => 'message_model'],
                ],
                [
                    'key' => 'analyze_image',
                    'name' => 'Analyze an image',
                    'description' => 'دریافت تصویر و استخراج جزئیات یا پاسخ به سوالات بر اساس آن.',
                    'config' => ['resource' => 'image', 'operation_image' => 'analyze_image'],
                ],
                [
                    'key' => 'generate_image',
                    'name' => 'Generate an image',
                    'description' => 'تولید تصویر با کیفیت بالا از طریق پرامپت با مدل Imagen 3.',
                    'config' => ['resource' => 'image', 'operation_image' => 'generate_image'],
                ],
                [
                    'key' => 'edit_image',
                    'name' => 'Edit an image',
                    'description' => 'ویرایش تصویر مرجع با اعمال پرامپت و تنظیمات جدید.',
                    'config' => ['resource' => 'image', 'operation_image' => 'edit_image'],
                ],
                [
                    'key' => 'analyze_audio',
                    'name' => 'Analyze audio',
                    'description' => 'شنیدن فایل صوتی و پاسخ به سوالات یا خلاصه‌سازی آن.',
                    'config' => ['resource' => 'audio', 'operation_audio' => 'analyze_audio'],
                ],
                [
                    'key' => 'transcribe_audio',
                    'name' => 'Transcribe a recording',
                    'description' => 'پیاده‌سازی و تبدیل کامل گفتار و فایل‌های صوتی به متن.',
                    'config' => ['resource' => 'audio', 'operation_audio' => 'transcribe_audio'],
                ],
                [
                    'key' => 'analyze_document',
                    'name' => 'Analyze document',
                    'description' => 'مطالعه و تحلیل فایل‌های PDF، متن و اسناد اداری.',
                    'config' => ['resource' => 'document', 'operation_document' => 'analyze_document'],
                ],
                [
                    'key' => 'upload_media_file',
                    'name' => 'Upload a media file',
                    'description' => 'آپلود فایل‌های سنگین در Google Files API برای استفاده در نودهای بعدی.',
                    'config' => ['resource' => 'media_file', 'operation_media_file' => 'upload_file'],
                ],
                [
                    'key' => 'create_search_store',
                    'name' => 'Create a File Search store',
                    'description' => 'ساخت مخزن معنایی جدید برای ذخیره و جستجوی فایل‌ها (Corpus).',
                    'config' => ['resource' => 'file_search', 'operation_file_search' => 'create_store'],
                ],
                [
                    'key' => 'list_search_stores',
                    'name' => 'List all File Search stores',
                    'description' => 'دریافت فهرست تمام مخازن ذخیره‌سازی فایل ایجاد شده.',
                    'config' => ['resource' => 'file_search', 'operation_file_search' => 'list_stores'],
                ],
                [
                    'key' => 'upload_to_search_store',
                    'name' => 'Upload a file to a File Search store',
                    'description' => 'ایندکس کردن متن و اسناد در مخزن جستجوی معنایی.',
                    'config' => ['resource' => 'file_search', 'operation_file_search' => 'upload_file_to_store'],
                ],
                [
                    'key' => 'delete_search_store',
                    'name' => 'Delete a File Search store',
                    'description' => 'حذف مخزن جستجوی فایل و اسناد مربوط به آن.',
                    'config' => ['resource' => 'file_search', 'operation_file_search' => 'delete_store'],
                ],
                [
                    'key' => 'analyze_video',
                    'name' => 'Analyze video',
                    'description' => 'مشاهده و درک فریم‌های ویدیو و پاسخ به سوالات مربوط به محتوای آن.',
                    'config' => ['resource' => 'video', 'operation_video' => 'analyze_video'],
                ],
                [
                    'key' => 'generate_video',
                    'name' => 'Generate a video',
                    'description' => 'تولید ساختار صحنه‌ها، سناریو و استوری‌بورد ویدیویی بر اساس پرامپت.',
                    'config' => ['resource' => 'video', 'operation_video' => 'generate_video'],
                ],
                [
                    'key' => 'download_video',
                    'name' => 'Download a video',
                    'description' => 'دانلود فایل ویدیویی از آدرس اینترنتی به صورت خروجی مستقیم.',
                    'config' => ['resource' => 'video', 'operation_video' => 'download_video'],
                ],
            ],
            color: 'blue',
        );
    }
}
