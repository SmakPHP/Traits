Реализация тестового задания.

1. Создание модели и миграции для задачи:

```bash
php artisan make:model Task -m
```

Это команда создаст файл модели `Task` в `app/Models` и файл миграции в `database/migrations`. Далее добавляем необходимые поля для задачи, например:

```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->boolean('completed')->default(false);
    $table->timestamp('due_date')->nullable();
    $table->timestamps();
});
```

Затем применяем миграцию:

```bash
php artisan migrate
```

2. Реализация API для CRUD операций:

- Создаем контроллер для задач:

```bash
php artisan make:controller TaskController --api
```

- В файле `app/Http/Controllers/TaskController.php` реализуем следующие методы:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    // Список задач
    public function index()
    {
        return Task::all();
    }

    // Создание задачи
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required',
            'description' => 'nullable',
            'due_date' => 'nullable|date',
        ]);

        $task = Task::create($validatedData);

        return $task;
    }

    // Показ задачи
    public function show(Task $task)
    {
        return $task;
    }

    // Обновление задачи
    public function update(Request $request, Task $task)
    {
        $validatedData = $request->validate([
            'title' => 'nullable',
            'description' => 'nullable',
            'completed' => 'nullable|boolean',
            'due_date' => 'nullable|date',
        ]);

        $task->update($validatedData);

        return $task;
    }

    // Удаление задачи
    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }
}
```

- Добавляем соответствующие маршруты в `routes/api.php`:

```php
Route::apiResource('tasks', TaskController::class);
```

3. Фильтрация задач по статусу или дате:

Для фильтрации можно использовать запросы с параметрами. Например, для фильтрации по статусу:

```php
public function index(Request $request)
{
    $tasks = Task::query();

    if ($request->has('completed')) {
        $tasks->where('completed', $request->input('completed'));
    }

    if ($request->has('due_date')) {
        $tasks->whereDate('due_date', $request->input('due_date'));
    }

    return $tasks->get();
}
```

Теперь, при запросе `/api/tasks?completed=true` будут возвращены только выполненные задачи, а `/api/tasks?due_date=2023-03-23` вернет задачи с указанной датой.
