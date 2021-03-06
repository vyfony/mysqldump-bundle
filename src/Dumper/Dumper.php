<?php

declare(strict_types=1);

/*
 * This file is part of VyfonyMysqldumpBundle project.
 *
 * (c) Anton Dyshkant <vyshkant@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vyfony\Bundle\MysqldumpBundle\Dumper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Process\Process;
use Vyfony\Bundle\MysqldumpBundle\DumpResult\DumpResult;
use Vyfony\Bundle\MysqldumpBundle\DumpResult\DumpResultInterface;

/**
 * @author Anton Dyshkant <vyshkant@gmail.com>
 */
final class Dumper implements DumperInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function dumpAndWriteToFile(string $pathToDumpFile): DumpResultInterface
    {
        $dumpResult = $this->dump();

        if ($dumpResult->isSuccessful()) {
            file_put_contents($pathToDumpFile, $dumpResult->getOutput());
        }

        return $dumpResult;
    }

    public function dump(): DumpResultInterface
    {
        $process = new Process(
            [
                'mysqldump',
                '--skip-dump-date',
                '--skip-extended-insert',
                '-h',
                $this->registry->getConnection()->getHost(),
                '-P',
                $this->registry->getConnection()->getPort(),
                '-u',
                $this->registry->getConnection()->getUsername(),
                '-p'.$this->registry->getConnection()->getPassword(),
                $this->registry->getConnection()->getDatabase(),
            ]
        );

        $output = [];
        $errorOutput = [];

        $exitCode = $process->run(
            function (string $outputType, string $pieceOfOutput) use (&$output, &$errorOutput): void {
                if (Process::OUT === $outputType) {
                    $output[] = $pieceOfOutput;
                } elseif (Process::ERR === $outputType) {
                    $errorOutput[] = $pieceOfOutput;
                }
            }
        );

        return new DumpResult($exitCode, implode('', $output), implode('', $errorOutput));
    }
}
