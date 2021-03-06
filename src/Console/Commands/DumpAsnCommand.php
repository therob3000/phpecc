<?php

namespace Mdanter\Ecc\Console\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PHPASN1\ASN_Object;

class DumpAsnCommand extends AbstractCommand
{

    protected function configure()
    {
        $this->setName('dump-asn')
            ->setDescription('Dumps the ASN.1 object structure.')
            ->addArgument('data', InputArgument::OPTIONAL)
            ->addOption('infile', null, InputOption::VALUE_OPTIONAL)
            ->addOption('in', null, InputOption::VALUE_OPTIONAL, 'Input format (der or pem). Defaults to pem.', 'pem');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loader = $this->getLoader($input, 'in');
        $data = $this->getPrivateKeyData($input, $loader, 'infile', 'data');

        $asnObject = ASN_Object::fromBinary(base64_decode($data));

        throw new \RuntimeException('Command not available.')
    }
}
