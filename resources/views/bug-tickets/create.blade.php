<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Bug Ticket</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="max-w-2xl mx-auto px-4 py-10">

    <div class="mb-6 flex items-center gap-2">
        <a href="{{ route('bug-tickets.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← Back</a>
        <h1 class="text-2xl font-bold text-gray-800 ml-2">New Bug Ticket</h1>
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 mb-6 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('bug-tickets.store') }}"
          enctype="multipart/form-data"
          class="bg-white rounded-xl shadow p-6 space-y-5">
        @csrf

        {{-- Title --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ticket Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="{{ old('title') }}"
                   placeholder="e.g. User login returns 500 error"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('title') border-red-400 @enderror">
        </div>

        {{-- Module + Priority row --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Module <span class="text-red-500">*</span></label>
                <input type="text" name="module" value="{{ old('module') }}"
                       placeholder="e.g. Auth, Orders, Reports"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('module') border-red-400 @enderror">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority <span class="text-red-500">*</span></label>
                <select name="priority"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('priority') border-red-400 @enderror">
                    @foreach(['low','medium','high','critical'] as $p)
                        <option value="{{ $p }}" {{ old('priority','medium') === $p ? 'selected' : '' }}>
                            {{ ucfirst($p) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Laravel Version --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Laravel Version <span class="text-red-500">*</span></label>
            <select name="laravel_version"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @foreach(['12.x','11.x','10.x','9.x','8.x'] as $v)
                    <option value="{{ $v }}" {{ old('laravel_version','12.x') === $v ? 'selected' : '' }}>
                        Laravel {{ $v }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-gray-400 text-xs">(optional)</span></label>
            <textarea name="description" rows="3"
                      placeholder="Additional context about the bug…"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('description') }}</textarea>
        </div>

        {{-- Screenshot upload --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Screenshot <span class="text-red-500">*</span>
                <span class="text-gray-400 text-xs ml-1">— highlight or circle the problem area</span>
            </label>
            <div id="drop-zone"
                 class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-blue-400 transition @error('screenshot') border-red-400 @enderror">
                <input type="file" name="screenshot" id="screenshot-input"
                       accept="image/png,image/jpeg,image/gif,image/webp"
                       class="hidden">
                <div id="drop-label">
                    <p class="text-gray-400 text-sm">Drag & drop or <span class="text-blue-500 underline">browse</span></p>
                    <p class="text-gray-300 text-xs mt-1">PNG, JPG, GIF, WEBP — max 10 MB</p>
                </div>
                <img id="preview" class="hidden mx-auto max-h-48 mt-3 rounded-lg" alt="Preview">
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition text-sm">
            Submit Ticket
        </button>
    </form>
</div>

<script>
    const input    = document.getElementById('screenshot-input');
    const dropZone = document.getElementById('drop-zone');
    const preview  = document.getElementById('preview');
    const dropLabel= document.getElementById('drop-label');

    dropZone.addEventListener('click', () => input.click());

    dropZone.addEventListener('dragover', e => {
        e.preventDefault();
        dropZone.classList.add('border-blue-400');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-blue-400');
    });

    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        input.files = e.dataTransfer.files;
        showPreview(e.dataTransfer.files[0]);
    });

    input.addEventListener('change', () => {
        if (input.files[0]) showPreview(input.files[0]);
    });

    function showPreview(file) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            dropLabel.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }
</script>

</body>
</html>
