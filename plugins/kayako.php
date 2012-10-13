<?php

namespace Minion\Plugins;

class KayakoPlugin extends \Minion\Plugin {

    public function getTicket ($ticket) {
        $kdb = $this->conf('KayakoDB');
        if (is_null($kdb)) {
            $this->Minion->log('Kayako plugin needs KayakoDB configured.', 'WARNING');
        } else {
            $condition = 'WHERE ticketid = ? or ticketmaskid = ?';
            if ($ticket == 'last') {
                $condition = 'ORDER BY ticketid DESC LIMIT 1';
            }
            $statement = $this->Minion->state['DB']->prepare("SELECT ticketid, ticketmaskid, fullname, email, subject, DATE_FORMAT(from_unixtime(dateline), '%Y-%m-%d %H:%i:%s') as created, date_format(from_unixtime(lastactivity), '%Y-%m-%d %H:%i:%s') as last, totalreplies FROM $kdb.swtickets $condition");
            $statement->execute(array($ticket, $ticket));
            $results = $statement->fetchAll();
            if (count($results)) {
                return $results[0];
            }
        }
        return false;
    }

}

$Kayako = new KayakoPlugin(
    'Kayako',
    'Looks up basic Kayako ticket info.',
    'Ryan N. Freebern / ryan@freebern.org'
);

return $Kayako
    ->on('PRIVMSG', function (&$minion, &$data) use ($Kayako) {
        if (isset($data['arguments'][0]) and preg_match('/^[#&][^ \a\0\012\015,:]+/', $data['arguments'][0])) {
            $ticketIDs = array();
            list ($command, $arguments) = $Kayako->simpleCommand($data);
            if ($command == 'ticket') {
                if (count($arguments)) {
                    $ticketIDs = $arguments;
                }
            } else {
                $matches = $Kayako->matchCommand($data, '/ticketid=(\d+)/');
                if ($matches) {
                    $ticketIDs = $matches;
                }
            }

            foreach ($ticketIDs as $ticketID) {
                $ticket = $Kayako->getTicket($ticketID);
                if ($ticket) {
                    $minion->msg("Ticket {$ticket['ticketmaskid']}: {$ticket['subject']} [{$ticket['fullname']} / {$ticket['email']}] [Created {$ticket['created']}] [Updated {$ticket['last']}] [{$ticket['totalreplies']} replies] http://support.unionstreetmedia.com/staff/?_m=tickets&_a=viewticket&ticketid={$ticket['ticketid']}", $data['arguments'][0]);
                }
            }
        }
    });
