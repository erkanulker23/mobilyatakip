import { Injectable, OnModuleInit } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import * as bcrypt from 'bcrypt';
import { User } from '../entities/user.entity';
import { UserRole } from '../common/enums/role.enum';

const SUPERADMIN_EMAIL = 'erkanulker0@gmail.com';
const SUPERADMIN_PASSWORD = 'password';
const SUPERADMIN_NAME = 'Superadmin';

@Injectable()
export class SuperadminSeedService implements OnModuleInit {
  constructor(
    @InjectRepository(User)
    private userRepo: Repository<User>,
  ) {}

  async onModuleInit() {
    const existing = await this.userRepo.findOne({ where: { email: SUPERADMIN_EMAIL } });
    if (existing) return;
    const hash = await bcrypt.hash(SUPERADMIN_PASSWORD, 10);
    await this.userRepo.save(
      this.userRepo.create({
        email: SUPERADMIN_EMAIL,
        passwordHash: hash,
        name: SUPERADMIN_NAME,
        role: UserRole.ADMIN,
        isActive: true,
      }),
    );
    console.log(`[Seed] Superadmin oluşturuldu: ${SUPERADMIN_EMAIL}`);
  }
}
