  <!DOCTYPE html>
  <html lang="en">

  <head>
      <meta charset="UTF-8">
      <title>Dashboard - Gatherly</title>
      <script src="https://cdn.tailwindcss.com"></script>
  </head>

  <body class="bg-gray-100 min-h-screen">

      <!-- Top Navbar -->
      <header class="bg-white shadow">
          <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
              <h1 class="text-xl font-semibold"></h1>
              <div class="flex items-center gap-4">
                  <span class="text-sm text-gray-700">
                      Hi, <strong>{{ auth()->user()->name }}</strong>
                  </span>
                  <form method="POST" action="{{ route('logout') }}">
                      @csrf
                      <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-1 rounded">
                          Logout
                      </button>
                  </form>
              </div>
          </div>
      </header>

      <!-- Main Layout -->
      <div class="flex min-h-screen">

          <!-- Sidebar -->
          <aside class="w-72 bg-white shadow-lg p-4 flex flex-col">
              <div class="text-2xl font-bold text-blue-600 mb-6">Gatherly</div>

              <!-- Search -->
              <div class="mb-6">
                  <input type="text" placeholder="Search communities..."
                      class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring focus:ring-blue-300">
              </div>

              <!-- Nav -->
              <nav class="space-y-2">
                  <a href="#" class="block px-3 py-2 rounded bg-blue-50 text-blue-600 font-medium">Feed</a>
                  <a href="#" class="block px-3 py-2 rounded hover:bg-gray-100">Events</a>
                  <a href="#" class="block px-3 py-2 rounded hover:bg-gray-100">
                      Messages
                      <span class="ml-1 text-xs bg-blue-600 text-white px-2 py-0.5 rounded-full">0</span>
                  </a>
              </nav>

              <!-- Communities -->
              <h3 class="mt-6 text-sm font-semibold text-gray-500">My Communities</h3>
              <div class="mt-2 space-y-2">
                  {{-- Will be populated dynamically --}}
              </div>

              <!-- User Profile at bottom -->

              <div class="mt-auto flex items-center gap-3 border-t pt-4">
                  <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold">
                      {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                  </div>
                  <div>
                      <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                      <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                  </div>
              </div>
          </aside>

          <!-- Main Content -->
          <main class="flex-1 p-6 space-y-6">

              <!-- Tabs -->
              <div class="flex space-x-6 border-b">
                  <a href="#" class="py-2 border-b-2 border-blue-600 text-blue-600 font-medium">Feed</a>
                  <a href="#" class="py-2 text-gray-600 hover:text-gray-800">Members</a>
                  <a href="#" class="py-2 text-gray-600 hover:text-gray-800">Events</a>
              </div>

              <!-- Community Header -->
              <div class="bg-white rounded-lg shadow overflow-hidden">
                  <div class="w-full h-48 bg-gray-200"></div>
                  <div class="p-6">
                      <h2 class="text-2xl font-bold">{{ $community->name ?? 'Community Name' }}</h2>
                      <p class="text-gray-600">{{ $community->description ?? '' }}</p>
                      <div class="flex gap-6 mt-3 text-sm text-gray-500">
                          <span>{{ $community->members_count ?? 0 }} members</span>
                          <span>{{ $community->active_count ?? 0 }} active this week</span>
                          <span>{{ $community->events_count ?? 0 }} events</span>
                      </div>
                  </div>
              </div>

              <!-- Info Boxes -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div class="bg-white p-4 rounded-lg shadow">
                      <h3 class="font-semibold mb-2">Community Activity</h3>
                      <p class="text-2xl font-bold">{{ $stats['posts'] ?? 0 }}</p>
                      <p class="text-sm text-gray-500">Total Posts</p>
                      <p class="mt-2 text-green-600">{{ $stats['online_now'] ?? 0 }} Online Now</p>
                  </div>
                  <div class="bg-white p-4 rounded-lg shadow">
                      <h3 class="font-semibold mb-2">Community Leaders</h3>
                      <ul class="text-sm text-gray-700 space-y-1">
                          {{-- Dynamically list leaders --}}
                      </ul>
                  </div>
                  <div class="bg-white p-4 rounded-lg shadow">
                      <h3 class="font-semibold mb-2">Quick Info</h3>
                      <ul class="text-sm text-gray-700 space-y-1">
                          {{-- Dynamically filled --}}
                      </ul>
                  </div>
              </div>

              <!-- Post Box (Chat Style with Photo Upload) -->
              <div class="bg-white p-4 rounded-lg shadow flex flex-col">
                  <p class="text-gray-600 mb-3">What’s happening in {{ $community->name ?? 'this community' }}?</p>

                  <!-- Input Area -->
                  <div class="flex gap-2 mb-2 items-center">
                      <input type="text" placeholder="Write a post for review..."
                          class="flex-1 px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring focus:ring-blue-300">

                      <!-- Photo Upload Button -->
                      <label for="photo-upload"
                          class="cursor-pointer px-3 py-2 bg-gray-200 rounded text-gray-600 hover:bg-gray-300">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                              stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 7v10c0 1.104.896 2 2 2h14c1.104 0 2-.896 2-2V7c0-1.104-.896-2-2-2H5c-1.104 0-2 .896-2 2zM5 7l7 7 4-4 3 3" />
                          </svg>
                      </label>
                      <input type="file" id="photo-upload" class="hidden">

                      <button class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Post</button>
                  </div>
              </div>
          </main>
      </div>
  </body>

  </html>
