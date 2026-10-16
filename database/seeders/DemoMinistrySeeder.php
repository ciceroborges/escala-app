<?php

namespace Database\Seeders;

use App\Enums\MemberRole;
use App\Enums\SongCategoryLevel;
use App\Models\Member;
use App\Models\Position;
use App\Models\User;
use App\Services\Contracts\IMinistryService;
use App\Services\Contracts\IScheduleService;
use App\Services\Contracts\ISetlistService;
use App\Services\Contracts\ISongService;
use Illuminate\Database\Seeder;

/**
 * Ministério de exemplo com a equipe e o repertório reais de outubro/2026 — só para ambiente local.
 * Entre pelo login de desenvolvimento com cicero@exemplo.com (admin) ou o e-mail de qualquer membro.
 */
class DemoMinistrySeeder extends Seeder
{
    public function __construct(
        private readonly IMinistryService $ministries,
        private readonly IScheduleService $schedules,
        private readonly ISongService $songs,
        private readonly ISetlistService $setlist,
    ) {}

    public function run(): void
    {
        $admin = User::firstOrCreate(['email' => 'cicero@exemplo.com'], ['name' => 'Cícero']);
        $ministry = $this->ministries->create($admin, ['name' => 'Ministério de Louvor', 'church_name' => 'Igreja Local']);
        $positions = Position::where('ministry_id', $ministry->id)->pluck('id', 'name');

        // o criador já entra como admin; aqui ele também canta e toca baixo
        $me = Member::where('ministry_id', $ministry->id)->first();
        $me->update(['monthly_limit' => 2]);
        $me->positions()->sync([$positions['Vocal'], $positions['Baixo']]);

        $team = [
            ['Lucimar', ['Vocal'], null, 'Vocal', MemberRole::Admin],
            ['João Lucas', ['Teclado', 'Vocal'], null],
            ['Giandro', ['DM', 'Baixo'], 2, null, MemberRole::Admin],
            ['Dani', ['Vocal']], ['Cami', ['Vocal']], ['Léo', ['Vocal', 'Guitarra']],
            ['Patrick', ['Vocal', 'Violão']], ['Sara', ['Vocal']], ['Géssica', ['Vocal']], ['Ricardo', ['Vocal']],
            ['Assis', ['Bateria']], ['Eliézer', ['Bateria']], ['Rômulo', ['Mesa', 'Bateria']],
            ['Iury', ['Violão']], ['João Robert', ['Mesa']], ['Jack', ['Mesa']],
        ];

        $members = ['Cícero' => $me];
        foreach ($team as $row) {
            [$name, $roles, $limit, $fixed, $role] = $row + [2 => 2, 3 => null, 4 => MemberRole::Member];
            $member = Member::create([
                'ministry_id' => $ministry->id,
                'name' => $name,
                'email' => str($name)->ascii()->lower()->replace(' ', '.').'@exemplo.com',
                'role' => $role,
                'monthly_limit' => $limit,
                'fixed_position_id' => $fixed ? $positions[$fixed] : null,
            ]);
            $member->positions()->sync(array_map(fn ($r) => $positions[$r], $roles));
            $members[$name] = $member;
        }

        $rhythm = $this->songs->categories($ministry->id)->firstWhere('level', SongCategoryLevel::Primary)->options->pluck('id', 'name');
        $catalog = [
            ['Filho do Deus Vivo', 'Rápida', ['Lucimar' => 'E']],
            ['Eu Só Quero a Tua Presença', 'Lenta', ['Ricardo' => null]],
            ['Consagração', 'Lenta', ['Lucimar' => 'F']],
            ['Pão e Vinho', 'Média', ['Lucimar' => 'G', 'Léo' => 'C']],
            ['Tudo o Que Me Prometeu', 'Rápida', ['Patrick' => 'F#']],
            ['Levanto um Aleluia', 'Rápida', ['Patrick' => null]],
            ['Eu Me Lembro', 'Média', ['Patrick' => 'A']],
            ['Vida aos Sepulcros', 'Média', ['Sara' => 'C']],
            ['Clamo Jesus', 'Lenta', ['Lucimar' => 'C']],
            ['Vou Te Alegrar', 'Rápida', ['Géssica' => null]],
            ['Glorioso Dia', 'Rápida', ['Lucimar' => 'G']],
            ['Pai Nosso', 'Média', ['Lucimar' => 'A']],
            ['É Ele', 'Lenta', ['João Lucas' => null, 'Ricardo' => null]],
            ['Seremos Um', 'Rápida', ['Patrick' => null]],
            ['Aviva-nos', 'Média', ['Patrick' => ['A', 'D']]],
            ['Não Descansarei', 'Média', ['Patrick' => null]],
            ['Tempos Melhores', 'Lenta', ['Patrick' => null]],
        ];

        $songIds = [];
        foreach ($catalog as [$title, $tag, $keys]) {
            $song = $this->songs->create($ministry->id, [
                'title' => $title,
                'primary_option_id' => $rhythm[$tag],
                'keys' => collect($keys)->map(fn ($key, $name) => [
                    'member_id' => $members[$name]->id,
                    'musical_key' => is_array($key) ? $key[0] : $key,
                    'starting_key' => is_array($key) ? $key[1] : null,
                ])->values()->all(),
            ]);
            $songIds[$title] = $song->id;
        }

        // Outubro: escala real, publicada, com repertório
        $october = $this->schedules->open($ministry->id, 2026, 10);
        $real = [
            '2026-10-04' => ['Vocal' => ['Lucimar', 'Dani', 'Cami', 'Léo'], 'Teclado' => ['João Lucas'], 'Guitarra' => ['Léo'], 'DM' => ['Giandro'], 'Baixo' => ['Giandro'], 'Bateria' => ['Assis'], 'Mesa' => ['João Robert']],
            '2026-10-11' => ['Vocal' => ['Lucimar', 'João Lucas', 'Patrick', 'Sara', 'Cícero'], 'Teclado' => ['João Lucas'], 'Violão' => ['Patrick'], 'Bateria' => ['Eliézer'], 'Mesa' => ['Rômulo']],
            '2026-10-18' => ['Vocal' => ['Lucimar', 'Géssica', 'Ricardo', 'João Lucas'], 'Teclado' => ['João Lucas'], 'Violão' => ['Iury'], 'DM' => ['Giandro'], 'Baixo' => ['Giandro'], 'Bateria' => ['Assis'], 'Mesa' => ['Rômulo']],
            '2026-10-25' => ['Vocal' => ['Lucimar', 'João Lucas', 'Patrick', 'Cícero'], 'Teclado' => ['João Lucas'], 'Baixo' => ['Cícero'], 'Bateria' => ['Rômulo'], 'Violão' => ['Patrick'], 'Mesa' => ['Jack']],
        ];
        $setlists = [
            '2026-10-18' => [['Vou Te Alegrar', ['Géssica']], ['Glorioso Dia', ['Lucimar']], ['Pai Nosso', ['Lucimar']], ['É Ele', ['João Lucas', 'Ricardo']], ['Eu Só Quero a Tua Presença', ['Ricardo']]],
            '2026-10-25' => [['Seremos Um', ['Patrick']], ['Aviva-nos', ['Patrick']], ['Não Descansarei', ['Patrick']], ['Clamo Jesus', ['Lucimar']], ['Tempos Melhores', ['Patrick']]],
        ];

        foreach ($october->worshipServices as $service) {
            $date = $service->date->toDateString();
            $rows = [];
            foreach ($real[$date] as $position => $names) {
                foreach ($names as $name) {
                    $rows[] = ['member_id' => $members[$name]->id, 'position_id' => $positions[$position]];
                }
            }
            $this->schedules->saveAssignments($service->load('schedule'), $rows);
            if (in_array($date, ['2026-10-04', '2026-10-18'])) {
                $this->schedules->updateService($service, ['notes' => 'Com DM']);
            }
            foreach ($setlists[$date] ?? [] as [$title, $singers]) {
                $this->setlist->add($service, ['song_id' => $songIds[$title], 'singers' => array_map(fn ($n) => $members[$n]->id, $singers)]);
            }
        }
        $this->schedules->publish($october);

        // Novembro: aberto, com algumas indisponibilidades, pronto para gerar
        $november = $this->schedules->open($ministry->id, 2026, 11, '2026-10-28');
        $services = $november->worshipServices;
        $off = ['Assis' => [0, 1], 'Dani' => [2], 'Patrick' => [0], 'Rômulo' => [3], 'Cami' => [1, 2], 'Lucimar' => []];
        foreach ($off as $name => $indexes) {
            $this->schedules->saveAvailability($members[$name], $november, array_map(fn ($i) => $services[$i]->id, $indexes));
        }
    }
}
