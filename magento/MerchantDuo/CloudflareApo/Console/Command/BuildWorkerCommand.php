<?php
declare(strict_types=1);
namespace MerchantDuo\CloudflareApo\Console\Command;
use Magento\Framework\Console\Cli;
use MerchantDuo\CloudflareApo\Api\BuildServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
class BuildWorkerCommand extends Command { public function __construct(private BuildServiceInterface $builds, ?string $name = null){parent::__construct($name);} protected function configure():void{$this->setName('cloudflare-apo:worker:build');} protected function execute(InputInterface $input,OutputInterface $output):int{$output->writeln(json_encode($this->builds->build(),JSON_PRETTY_PRINT));return Cli::RETURN_SUCCESS;} }
