<?php

namespace App\Controller;

use App\Form\BankStatementUploadType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Translation\TranslatorInterface;

final class BankStatementController extends AbstractController
{
	#[Route('/', name: 'app_bank_statement')]
	public function index(Request $request, TranslatorInterface $translator): Response
	{
		$form = $this->createForm(BankStatementUploadType::class);
		$form->handleRequest($request);

		$data = [];
		$headers = [];
		$statistics = [];

		if ($form->isSubmitted() && $form->isValid()) {
			$file = $form->get('csv_file')->getData();

			if (($handle = fopen($file->getPathname(), 'r')) !== false) {
				$headers = fgetcsv($handle, 1000, ';');

				while (($row = fgetcsv($handle, 1000, ';')) !== false) {
					$data[] = $row;
				}

				fclose($handle);
			}

			$cleanData = $this->cleanData($headers, $data);
			$headers = $cleanData[0];
			$data = $cleanData[1];
			$statistics = $this->generateStatistics($data);
		}

		$messages = $translator->getCatalogue($translator->getLocale())->all('messages');

		return $this->render('bank_statement/index.html.twig', [
			'messages' => $messages,
			'form' => $form,
			'headers' => $headers,
			'data' => $data,
			'statistics' => $statistics,
		]);
	}

	#[Route('/test', name: 'app_test_data')]
	public function test_data(Request $request, TranslatorInterface $translator): Response
	{
		$testCSV = $this->getParameter('kernel.project_dir') . '/data/data.csv';

		$uploadedFile = new UploadedFile($testCSV, 'data.csv', 'text/csv', null, true);

		$form = $this->createForm(BankStatementUploadType::class);
		$form->submit(['csv_file' => $uploadedFile]);

		// This is just copied over from the main route
		// TODO: Should move this into a function to avoid repetition
		$data = [];
		$headers = [];
		$statistics = [];

		if (($handle = fopen($testCSV, 'r')) !== false) {
			$headers = fgetcsv($handle, 1000, ';');

			while (($row = fgetcsv($handle, 1000, ';')) !== false) {
				$data[] = $row;
			}

			fclose($handle);
		}

		$cleanData = $this->cleanData($headers, $data);
		$headers = $cleanData[0];
		$data = $cleanData[1];
		$statistics = $this->generateStatistics($data);

		$messages = $translator->getCatalogue($translator->getLocale())->all('messages');

		return $this->render('bank_statement/index.html.twig', [
			'messages' => $messages,
			'form' => $form,
			'headers' => $headers,
			'data' => $data,
			'statistics' => $statistics,
		]);

		return $this->render('bank_statement/index.html.twig', [
			'form' => $form,
			'headers' => $headers,
			'data' => $data,
			'statistics' => $statistics,

		]);
	}

	private function cleanData(array $headers, array $data): array
	{
		/*
		 * $headers[1] = Buchungstag
		 * $headers[3] = Buchungstext
		 * $headers[4] = Verwendugszweck
		 * $headers[11] = Beguenstigter/Zahlungspflichtiger
		 * $headers[14] = Betrag
		 */
		$cleanHeaders = [$headers[1], $headers[3], $headers[4], $headers[11], $headers[14]];
		$cleanData = [];

		foreach ($data as $row) {
			$cleanRow = [
				'date' => $row[1],
				'type' => $row[3],
				'purpose' => $row[4],
				'recipient' => $row[11],
				'amount' => $row[14],
			];
			$cleanData[] = $cleanRow;
		}

		return [
			$cleanHeaders,
			$cleanData,
		];
	}

	private function generateStatistics(array $data): array
	{
		if (empty($data)) {
			return [];
		}

		// Get top 3 highest payments
		$sortedByAmount = $data;
		usort($sortedByAmount, fn($a, $b) => intval($a['amount']) > intval($b['amount']));
		$unique = [];
		$topPayments = [];
		foreach ($sortedByAmount as $payment) {
			if (!isset($unique[$payment['amount']])) {
				$unique[$payment['amount']] = true;
				$topPayments[] = $payment;
			}
		}
		$topPayments = array_slice($topPayments, 0, 5);

		// Get top 3 recipients
		$recipientCounts = [];
		foreach ($data as $transaction) {
			$recipient = $transaction['recipient'];
			if (!empty($recipient)) {
				$recipientCounts[$recipient]['name'] = $recipient;
				$recipientCounts[$recipient]['count'] = ($recipientCounts[$recipient]['count'] ?? 0) + 1;
				$recipientCounts[$recipient]['amount'] = ($recipientCounts[$recipient]['amount'] ?? 0) + intval($transaction['amount']);
			}
		}
		uasort($recipientCounts, fn($a, $b) => $b['count'] <=> $a['count']);
		$topRecipients = array_slice($recipientCounts, 0, 5, true);

		// Get most frequent transaction type
		$typeCounts = array_count_values(array_column($data, 'type'));
		arsort($typeCounts);
		$mostFrequentType = key($typeCounts);

		return [
			'top_payments' => $topPayments,
			'top_recipients' => $topRecipients,
			'most_frequent_type' => $mostFrequentType,
		];
	}
}
