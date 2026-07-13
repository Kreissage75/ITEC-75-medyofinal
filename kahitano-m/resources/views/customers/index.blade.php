@extends('layouts.app')

@section('title', 'Customer List')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/customers.css') }}">
@endpush

@section('content')
  <div class="card">
    <div class="card-head"><div class="card-title">CUSTOMER LIST</div></div>

    <table class="data-table">
      <thead>
        <tr><th>Name</th><th>Company</th><th>Email</th><th>Phone</th><th>Status</th><th>Last message</th></tr>
      </thead>
      <tbody>
        @forelse($customers as $customer)
          <tr>
            <td>
              <a href="{{ route('customers.show', $customer) }}" class="client-name-link" data-id="{{ $customer->id }}">
                {{ $customer->name }}
              </a>
            </td>
            <td>{{ $customer->company }}</td>
            <td>{{ $customer->email }}</td>
            <td>{{ $customer->phone }}</td>
            <td><span class="pill {{ $customer->status==='active' ? 'active' : 'general' }}">{{ ucfirst($customer->status) }}</span></td>
            <td style="color:var(--text-muted);">{{ $customer->last_message }}</td>
          </tr>
        @empty
          <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:20px;">No customer list yet.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:14px;">{{ $customers->links() }}</div>
  </div>

  <!-- Customer Profile Overlay Modal -->
  <div id="customerModalOverlay" class="customer-modal-overlay">
    <div class="customer-modal-container">
      <div class="customer-modal-header">
        <div class="customer-modal-title">CUSTOMER PROFILE</div>
        <button type="button" id="closeCustomerModal" class="customer-modal-close">&times;</button>
      </div>
      <div class="customer-modal-body" id="customerModalBody">
        <!-- Will be dynamically populated -->
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('customerModalOverlay');
  const container = overlay.querySelector('.customer-modal-container');
  const closeBtn = document.getElementById('closeCustomerModal');
  const body = document.getElementById('customerModalBody');

  // Colors mapping for avatar background based on customer initial
  const getAvatarColor = (initial) => {
    const code = initial.toUpperCase().charCodeAt(0);
    const colors = [
      { bg: '#d1c4e9', text: '#512da8' }, // Purple
      { bg: '#bbdefb', text: '#0d47a1' }, // Blue
      { bg: '#c8e6c9', text: '#1b5e20' }, // Green
      { bg: '#ffecb3', text: '#ff6f00' }, // Orange
      { bg: '#e1bee7', text: '#4a148c' }, // Pink
      { bg: '#b2dfdb', text: '#004d40' }, // Teal
      { bg: '#ffccbc', text: '#e65100' }  // Coral
    ];
    return colors[code % colors.length];
  };

  // Open modal and fetch data
  document.querySelectorAll('.client-name-link').forEach(link => {
    link.addEventListener('click', async (e) => {
      e.preventDefault();
      const customerId = link.getAttribute('data-id');
      
      // Open overlay & show loading spinner
      overlay.classList.add('active');
      body.innerHTML = `
        <div class="popup-loading">
          <div class="popup-spinner"></div>
          <div>Loading customer profile...</div>
        </div>
      `;

      try {
        const response = await fetch(`/customers/${customerId}`, {
          headers: { 'Accept': 'application/json' }
        });
        
        if (!response.ok) throw new Error('Failed to fetch customer details.');
        
        const data = await response.json();
        
        // Dynamic colors for avatar
        const color = getAvatarColor(data.initial);

        // Generate tickets list HTML
        let ticketsHtml = '';
        if (data.tickets && data.tickets.length > 0) {
          ticketsHtml = `<div class="popup-ticket-list">`;
          data.tickets.forEach(ticket => {
            const statusClass = ticket.status_class || ticket.status;
            ticketsHtml += `
              <div class="popup-ticket-row">
                <div class="popup-ticket-main">
                  <a href="/tickets/${ticket.id}" class="popup-ticket-code">#${ticket.code}</a>
                  <span class="popup-ticket-subject" title="${ticket.subject}">${ticket.subject}</span>
                </div>
                <div class="popup-ticket-meta">
                  <span class="popup-ticket-pill ${statusClass}">${ticket.status}</span>
                  <span class="popup-ticket-time">${ticket.time_ago}</span>
                </div>
              </div>
            `;
          });
          ticketsHtml += `</div>`;
        } else {
          ticketsHtml = `<div class="popup-empty-tickets">No tickets for this customer.</div>`;
        }

        // Render profile content
        body.innerHTML = `
          <div class="customer-profile-hero">
            <div class="customer-profile-avatar" style="background-color: ${color.bg}; color: ${color.text};">
              ${data.initial}
            </div>
            <div class="customer-profile-info">
              <h2 class="customer-profile-name">${data.name}</h2>
              <div class="customer-profile-contact">
                <span>${data.company}</span>
                <span class="divider">&middot;</span>
                <span>${data.email}</span>
                <span class="divider">&middot;</span>
                <span>${data.phone}</span>
              </div>
            </div>
          </div>

          <div class="customer-modal-grid">
            <!-- Left Side: Recent Tickets -->
            <div class="customer-modal-left">
              <h3 class="customer-modal-section-title">Recent Tickets</h3>
              ${ticketsHtml}
            </div>

            <!-- Right Side: Stats -->
            <div class="customer-modal-right">
              <div class="popup-stat-card all">
                <div class="popup-stat-label">All</div>
                <div class="popup-stat-val">${data.stats.all}</div>
              </div>
              <div class="popup-stat-card pending">
                <div class="popup-stat-label">Pending</div>
                <div class="popup-stat-val">${data.stats.pending}</div>
              </div>
              <div class="popup-stat-card resolved">
                <div class="popup-stat-label">Resolved</div>
                <div class="popup-stat-val">${data.stats.resolved}</div>
              </div>
            </div>
          </div>
        `;
      } catch (error) {
        console.error(error);
        body.innerHTML = `
          <div class="popup-empty-tickets" style="color: #ef4444;">
            <svg style="width: 48px; height: 48px; margin-bottom: 8px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>Failed to load customer profile. Please try again.</div>
          </div>
        `;
      }
    });
  });

  // Close modal functions
  const closeModal = () => {
    overlay.classList.remove('active');
  };

  closeBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) closeModal();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlay.classList.contains('active')) {
      closeModal();
    }
  });
});
</script>
@endpush
