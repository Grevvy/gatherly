<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  @vite(['resources/js/app.jsx'])
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-white shadow">
    <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
      <h1 class="text-xl font-semibold">Gatherly Dashboard</h1>

      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-700">
          Hi, <strong>{{ auth()->user()->name }}</strong>
        </span>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button
            type="submit"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-1 rounded"
          >
            Logout
          </button>
        </form>
      </div>
    </div>
  </header>

  <main class="max-w-5xl mx-auto px-6 py-8">
    @if (session('status'))
      <div class="mb-4 p-3 rounded bg-green-50 text-green-700">
        {{ session('status') }}
      </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
      <section class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Getting Started</h2>
        <p class="text-sm text-gray-600">
          You’re logged in. From here, wire up your communities, posts, and approval workflows.
        </p>
        <ul class="mt-4 text-sm list-disc list-inside text-gray-700">
          <li>Create a community</li>
          <li>Invite members</li>
          <li>Approve pending posts</li>
        </ul>
      </section>

      <section class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Quick Links</h2>
        <div class="flex flex-col gap-2">
          <a href="#" class="text-blue-600 hover:underline">My Communities</a>
          <a href="#" class="text-blue-600 hover:underline">Pending Approvals</a>
          <a href="#" class="text-blue-600 hover:underline">Profile</a>
        </div>
      </section>
    </div>
  </main>
</body>
</html>
