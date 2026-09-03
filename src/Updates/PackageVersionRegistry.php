<?php

namespace Pcteckserv\CmsCore\Updates;

use Composer\InstalledVersions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Pcteckserv\CmsCore\Plugins\PluginCatalog;

class PackageVersionRegistry
{
    public function __construct(
        private readonly GitTagUpdateChecker $updateChecker,
        private readonly PluginCatalog $plugins,
    ) {
    }

    /**
     * @return Collection<int, InstalledPackage>
     */
    public function sync(): Collection
    {
        $packages = $this->configuredPackages();

        $channel = config('cms-core.updates.channel', 'stable');

        return $packages->map(function (string $package) use ($channel): InstalledPackage {
            $installedVersion = $this->installedVersion($package);

            DB::table('cms_installed_packages')->updateOrInsert(
                ['name' => $package],
                [
                    'installed_version' => $installedVersion,
                    'channel' => $channel,
                    'checked_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            return new InstalledPackage($package, $installedVersion, null, $channel, now()->toDateTimeString());
        });
    }

    /**
     * @return Collection<int, InstalledPackage>
     */
    public function checkRemoteUpdates(): Collection
    {
        $this->sync();

        $packages = $this->configuredPackages();

        foreach ($packages as $package) {
            $availableVersion = $this->updateChecker->latestVersion($package);
            $installedVersion = $this->installedVersion($package);

            if ($availableVersion === null) {
                $currentAvailableVersion = DB::table('cms_installed_packages')
                    ->where('name', $package)
                    ->value('available_version');

                $fallbackAvailableVersion = is_string($installedVersion)
                    && is_string($currentAvailableVersion)
                    && version_compare($this->normalizeVersion($installedVersion), $this->normalizeVersion($currentAvailableVersion), '>')
                        ? $installedVersion
                        : $currentAvailableVersion;

                DB::table('cms_installed_packages')
                    ->where('name', $package)
                    ->update([
                        'available_version' => $fallbackAvailableVersion,
                        'checked_at' => now(),
                        'updated_at' => now(),
                    ]);

                continue;
            }

            if (is_string($installedVersion) && version_compare(
                $this->normalizeVersion($installedVersion),
                $this->normalizeVersion($availableVersion),
                '>'
            )) {
                $availableVersion = $installedVersion;
            }

            DB::table('cms_installed_packages')
                ->where('name', $package)
                ->update([
                    'available_version' => $availableVersion,
                    'checked_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return $this->all();
    }

    /**
     * @return Collection<int, InstalledPackage>
     */
    public function all(): Collection
    {
        return DB::table('cms_installed_packages')
            ->orderBy('name')
            ->get()
            ->map(fn (object $package): InstalledPackage => new InstalledPackage(
                $package->name,
                $package->installed_version,
                $package->available_version,
                $package->channel,
                $package->checked_at,
            ));
    }

    private function installedVersion(string $package): ?string
    {
        if (! InstalledVersions::isInstalled($package)) {
            return null;
        }

        return InstalledVersions::getPrettyVersion($package) ?: InstalledVersions::getVersion($package);
    }

    /**
     * @return Collection<int, string>
     */
    private function configuredPackages(): Collection
    {
        return collect(config('cms-core.updates.packages', []))
            ->merge($this->plugins->packages())
            ->filter(fn (mixed $package): bool => is_string($package) && $package !== '')
            ->unique()
            ->values();
    }

    private function normalizeVersion(string $version): string
    {
        return ltrim($version, 'v');
    }
}
