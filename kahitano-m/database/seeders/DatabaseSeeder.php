<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Customer;
use App\Models\SlaRule;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::insert([
            ['name' => 'Maria Santos', 'email' => 'maria.santos@example.com', 'phone' => '+63 917 555 0110', 'company' => 'Greenfield Farms', 'status' => 'active', 'last_message' => 'Thank you, issue resolved!', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Juan Dela Cruz', 'email' => 'juan.delacruz@example.com', 'phone' => '+63 917 555 0111', 'company' => 'AgriCorp', 'status' => 'active', 'last_message' => 'Still waiting on a reply.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Liza Reyes', 'email' => 'liza.reyes@example.com', 'phone' => '+63 917 555 0112', 'company' => 'Sunrise Poultry', 'status' => 'inactive', 'last_message' => 'Can you check my invoice?', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Carlos Bautista', 'email' => 'carlos.bautista@example.com', 'phone' => '+63 917 555 0113', 'company' => 'Bautista Livestock', 'status' => 'active', 'last_message' => 'Appreciate the fast support.', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Ticket::insert([
            ['code' => 'TCK-0001', 'subject' => 'Login issues on mobile app', 'customer_name' => 'Maria Santos', 'priority' => 'High', 'status' => 'open', 'assigned_to' => 'Agent Uy', 'description' => 'Customer cannot log in to the mobile app after updating to the latest version.', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'TCK-0002', 'subject' => 'Feed order delayed', 'customer_name' => 'Juan Dela Cruz', 'priority' => 'Medium', 'status' => 'pending', 'assigned_to' => 'Agent Reyes', 'description' => 'Order #4521 has not arrived after 5 business days.', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'TCK-0003', 'subject' => 'Invoice discrepancy', 'customer_name' => 'Liza Reyes', 'priority' => 'Low', 'status' => 'resolved', 'assigned_to' => 'Agent Uy', 'description' => 'Invoice amount does not match the quoted price.', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'TCK-0004', 'subject' => 'General product inquiry', 'customer_name' => 'Carlos Bautista', 'priority' => 'General', 'status' => 'open', 'assigned_to' => null, 'description' => 'Asking about bulk pricing for livestock feed.', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Article::insert([
            ['title' => 'How to reset your password', 'category' => 'Account', 'body' => 'Step-by-step guide to resetting your AmbatuGrow account password.', 'views' => 1345, 'helpful_count' => 210, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Tracking your feed order', 'category' => 'Orders', 'body' => 'Learn how to track the status of your feed and supply orders.', 'views' => 980, 'helpful_count' => 150, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Understanding your invoice', 'category' => 'Billing', 'body' => 'A breakdown of the charges that appear on your monthly invoice.', 'views' => 640, 'helpful_count' => 88, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Contacting support after hours', 'category' => 'Support', 'body' => 'What to do if you need help outside of business hours.', 'views' => 402, 'helpful_count' => 55, 'created_at' => now(), 'updated_at' => now()],
        ]);

        SlaRule::insert([
            ['priority' => 'High', 'name' => 'High Priority Support', 'description' => 'For urgent issues that require immediate attention and fast resolution.', 'response_hours' => 2, 'response_minutes' => 0, 'resolution_hours' => 24, 'resolution_minutes' => 0, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['priority' => 'Medium', 'name' => 'Medium Priority Support', 'description' => 'For issues requiring timely attention within the same business day.', 'response_hours' => 6, 'response_minutes' => 0, 'resolution_hours' => 48, 'resolution_minutes' => 0, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['priority' => 'Low', 'name' => 'Low Priority Support', 'description' => 'For non-urgent issues that can be scheduled for later resolution.', 'response_hours' => 12, 'response_minutes' => 0, 'resolution_hours' => 72, 'resolution_minutes' => 0, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['priority' => 'General', 'name' => 'General Support', 'description' => 'For general inquiries, questions, and account requests.', 'response_hours' => 8, 'response_minutes' => 0, 'resolution_hours' => 48, 'resolution_minutes' => 0, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
