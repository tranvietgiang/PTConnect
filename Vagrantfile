Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/focal64"
  config.vm.box_version = "20240821.0.1"

  # CHỈ dùng private network (nhanh + ổn định)
  config.vm.network "private_network", ip: "192.168.33.12"

  # Port Laravel
  config.vm.network "forwarded_port", guest: 8000, host: 8000

  # Sync folder
  config.vm.synced_folder ".", "/vagrant"

  # TỐI ƯU VirtualBox
  config.vm.provider "virtualbox" do |vb|
    vb.gui = false          # Tắt GUI (rất quan trọng)
    vb.memory = "8192"      # RAM 
    vb.cpus = 4             # CPU
  end

  # Provision (chỉ chạy lần đầu)
  config.vm.provision "shell", inline: <<-SHELL
    apt-get update -y

    # Tools cơ bản
    apt-get install -y git curl unzip net-tools

    # Apache (nếu cần)
    apt-get install -y apache2

    # Docker (nếu bạn dùng)
    apt-get install -y docker.io docker-compose
    usermod -aG docker vagrant

  SHELL

  config.vm.boot_timeout = 300
end