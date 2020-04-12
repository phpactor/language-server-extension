<?php

namespace Phpactor\Extension\LanguageServerCompletion\Util;

use LanguageServerProtocol\ParameterInformation;
use LanguageServerProtocol\SignatureHelp;
use LanguageServerProtocol\SignatureInformation;
use Phpactor\Completion\Core\SignatureHelp as PhpactorSignatureHelp;

class PhpactorToLspSignature
{
    public static function toLspSignatureHelp(PhpactorSignatureHelp $phpactorHelp): SignatureHelp
    {
        $help = new SignatureHelp();
        $help->activeParameter = $phpactorHelp->activeParameter();
        $help->activeSignature = $phpactorHelp->activeSignature();

        $signatures = [];
        foreach ($phpactorHelp->signatures() as $phpactorSignature) {
            $parameters = [];
            foreach ($phpactorSignature->parameters() as $phpactorParameter) {
                $parameters[] = new ParameterInformation(
                    $phpactorParameter->label(),
                    $phpactorParameter->documentation()
                );
            }

            $signatures[] = new SignatureInformation(
                $phpactorSignature->label(),
                $parameters,
                $phpactorSignature->documentation()
            );
        }

        $help->signatures = $signatures;

        return $help;
    }
}
