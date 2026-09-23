<?php

declare(strict_types=1);

// DEVELOPMENT ONLY: run once locally, then delete this file if no longer needed.

$pdo = require dirname(__DIR__) . '/config/database.php';

$departments = [
    ['Executive Management', 'Strategic leadership and overall bank governance.'],
    ['Retail Banking', 'Personal banking products and services for individual customers.'],
    ['Corporate Banking', 'Banking services and relationship management for business customers.'],
    ['Branches and Agencies', 'Day-to-day operations of bank branches and service points.'],
    ['Operations', 'Payment processing, account servicing, settlements, and back-office operations.'],
    ['Loans and Credit', 'Loan applications, credit analysis, approvals, and portfolio management.'],
    ['Treasury', 'Liquidity, cash management, funding, and financial market operations.'],
    ['Risk Management', 'Identification, assessment, and monitoring of banking risks.'],
    ['Compliance and AML', 'Regulatory compliance, anti-money laundering, and customer due diligence.'],
    ['Finance and Accounting', 'Financial reporting, accounting, budgeting, and financial controls.'],
    ['Information Technology', 'Technology infrastructure, applications, help desk, and IT security.'],
    ['Cybersecurity', 'Protection of systems, networks, data, and security incident response.'],
    ['Human Resources', 'Recruitment, employee relations, training, and workforce administration.'],
    ['Legal Affairs', 'Legal advice, contracts, disputes, and regulatory legal matters.'],
    ['Internal Audit', 'Independent reviews of controls, processes, and governance.'],
    ['Customer Service', 'Customer support, complaints, requests, and service quality.'],
    ['Marketing and Communications', 'Brand management, campaigns, communications, and public relations.'],
];

$find = $pdo->prepare('SELECT id FROM departments WHERE name = :name LIMIT 1');
$insert = $pdo->prepare('INSERT INTO departments (name, description) VALUES (:name, :description)');

$pdo->beginTransaction();
try {
    $added = 0;
    $skipped = 0;

    foreach ($departments as [$name, $description]) {
        $find->execute(['name' => $name]);
        if ($find->fetch() !== false) {
            $skipped++;
            continue;
        }

        $insert->execute(['name' => $name, 'description' => $description]);
        $added++;
    }

    $pdo->commit();
    echo "Departments added: {$added}\n";
    echo "Departments already present: {$skipped}\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Department seeding failed: {$exception->getMessage()}\n");
    exit(1);
}